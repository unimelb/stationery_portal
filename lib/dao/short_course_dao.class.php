<?php
/**
 * Short Course DAO
 * 
 * @package dao
 * @copyright University of Melbourne, 2009
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Required files
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/meid_sqlserver_dao.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/course/enrolment.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/link_dao.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/should_be_boolean.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/get_days_from_interval.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/short_course_email_dispatcher.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/mailbag.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/get_id_from_name.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/rearrange.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/writers/submission_writer.class.php");
/**
 * ShortCourseDao (Database Access Object)
 * based on daophp5
 * database access functions relating to short courses
 * 
 * @package dao
 */
class ShortCourseDao extends MEIDSQLServerDao
{
	/**
	 * constructor
	 * @param string database connection string
	 * (dbtype://user:pass@host/dbname)
	 */
	public function __construct($dsn) 
	{
		parent::__construct($dsn);
	}

	/**
	 * getCoursesByTypeName
	 * @param string type, a single word, limit 50 chars to identify the course type
	 * @return array of course names
	 */
	public function getCoursesByTypeName($type)
	{
		$type_id = $this->pluck('sc_course_type', null, array('name' => $type), 'course_type_id');
		$courses = array();
		if ($type_id !== false)
		{
			$courses = $this->suck('sc_course_type', array('course_code', 'course_name', 'active'), array('course_type_id' => $type_id));
		}
		return $courses;
	}

	/**
	 * getCoursesByEnrolment
	 * returns the list of course_codes associated with a particular enrolment_id
	 * or false if the enrolment_id does not exist or has no associated courses
	 * @param int enrolment_id
	 * @return array(string) $course_codes
	 */
	public function getCoursesByEnrolment($enrolment_id)
	{
		$courses = $this->suck('sc_signup', 'course_code', array('enrolment_id' => $enrolment_id));
		return $courses;
	}

	/**
	 * getEnrolmentComplete
	 * determines whether all of the courses of an enrolment have been completed. 
	 * This function is called by SubmissionWriter
	 * @param int enrolment_id
	 * @return boolean TRUE if every module in the course has been completed
	 */
	public function getEnrolmentComplete($enrolment_id)
	{
		$complete = false;
		$sql = 'select enrolment_id as completed_enrolments from sc_enrolment except select enrolment_id from sc_incomplete_modules';
	//select enrolment_id as completed_enrolments from sc_enrolment where enrolment_id not in (select enrolment_id from sc_incomplete_modules)';
		$result = $this->retrieve($sql);
		$retrieved_list = $this->processMulticolumnResults($result, 'completed_enrolments');
		if (in_array($enrolment_id, $retrieved_list))
		{
			$complete = true;
		}
		return $complete;
	}

	/**
	 * getEnrolmentIncomplete
	 * determines whether an enrolment is incomplete 
	 * That is, time limit has expired (it is finished), but has outstanding modules.
	 * @param int enrolment_id
	 * @return boolean TRUE if the module is incomplete
	 */
	public function getEnrolmentIncomplete($enrolment_id)
	{
		$complete = false;
		$sql = 'select enrolment_id as incompleted_enrolments from sc_enrolment_view where now() > end_date and enrolment_id in (select enrolment_id from sc_incomplete_modules)';
		$result = $this->retrieve($sql);
		$retrieved_list = $this->processMulticolumnResults($result, 'incompleted_enrolments');
		if (in_array($enrolment_id, $retrieved_list))
		{
			$complete = true;
		}
		return $complete;
	}

	/**
	 * getEnrolmentEvaluated
	 * determines whether an enrolment has been evaluated. 
	 * This function is called by SubmissionWriter
	 * @param int enrolment_id
	 * @return boolean TRUE if the enrolment has been evaluated
	 */
	public function getEnrolmentEvaluated($enrolment_id)
	{
		$evaluated = false;
		$enrolment_details = $this->pluck('sc_enrolment_view', null, array('enrolment_id' => $enrolment_id));
		if (isset($enrolment_details['evaluated']) and shouldBeBoolean($enrolment_details['evaluated']))
		{
			$evaluated = true;
		}
		return $evaluated;
	}

	/**
	 * getEnrolmentDetails
	 *
	 * get meta information about an enrolment including the list of courses undertaken
	 * @param int enrolment_id
	 * @return array
	 */
	public function getEnrolmentDetails($enrolment_id)
	{
		$sc_enrolment = $this->pluck('sc_enrolment_view', null, array('enrolment_id' => $enrolment_id));
		$sc_enrolment['courses'] = $this->suck('sc_signup', 'course_code', array('enrolment_id' => $enrolment_id));
		return $sc_enrolment;
	}

	/**
	 * getCourseDetails by course_code
	 * @param string (20 chars) $course_code
	 * @return array of details: course_name, course_type_id, active (should be boolean), 
	 * parent_course_code, link, description
	 */
	public function getCourseDetails($course_code)
	{
		return $this->pluck('sc_course', null, array('course_code'=>$course_code));
	}

	/**
	 * getCourseName
	 * a subset of the above function. Only returns the name of the course
	 * ironically, it's a bigger function
	 * @param string (20 chars) $course_code
	 * @return string, the course name only
	 */
	public function getCourseName($course_code)
	{
		$result = null;
		$details = $this->getCourseDetails($course_code);
		if (isset($details['course_name']))
		{
			$result = $details['course_name'];
		}
		return $result;
	}

	/**
	 * getCourseCodeFromName
	 * The complement to the above function
	 * Used in the diagnostic for the short course
	 * @param string $course_name
	 * @return string, the course code which matches the name (exactly)
	 */
	public function getCourseCodeFromName($course_name)
	{
		$result = null;
		$details = $this->pluck('sc_course', null, array('course_name'=>$course_name), 'course_code');
		if (isset($details['course_code']))
		{
			$result = $details['course_code'];
		}
		return $result;
	}

	/**
	 * getModulesByCourseCode
	 * should be a recursive fuction
	 * But then, probably sub-courses will only go down one level from the top
	 * (as the writing course does), so it would most likely be overengineering.
	 * @param string
	 * @return array of int module_ids
	 */
	public function getModulesByCourseCode($course_code)
	{
		$module_list = array();
		$child_courses = $this->findChildCourses($course_code);
		if (count($child_courses) > 0)
		{
			foreach ($child_courses as $c)
			{
				$courses = $this->getModulesByCourseCode($c);
				$module_list = array_merge($module_list, $courses);
			}
		}
		else
		{
			$sql = "SELECT module_id FROM sc_course_module WHERE course_code = '" . $this->da->escape($course_code) . "' ORDER BY list_order;";
			$result = $this->retrieve($sql);
			$retrieved_list = $this->processMulticolumnResults($result, 'module_id');
			$module_list = $retrieved_list;
		}
		return $module_list;
	}

	/**
	 * getModuleDetails
	 * @return array of array details as per sc_module table:
	 * 	keys: module_id, name, description, assessment_hours, moderated, question_sets (array of int)
	 * plus a list of associated question_sets ('question_sets')
	 * or FALSE if not found
	 */
	public function getModuleDetails($module_id, $course_code=null)
	{
		$details = $this->pluck('sc_module', null, array('module_id' => $module_id));
		$qsets = $this->suck('sc_module_qset', 'qset_id', array('module_id' => $module_id));
		if (is_array($details))
		{	
			$details['question_sets'] = $qsets;
		}
		if (! is_null($course_code))
		{
			$link = $this->pluck('sc_course_module', null, array('module_id' => $module_id, 'course_code' => $course_code), 'link');
			
			if (isset($link['link']))
			{
				$url = $this->pluck('link', null, array ('keyword' => $link['link']));
				$details['link'] = $link['link'];
				$details['url'] = $url['url'];
			}
		}
		return $details;
	}

	/**
	 * getModuleByModuleIdCourseCodeAndEnrolmentId
	 *
	 * @param int module_id
	 * @param string course_code
	 * @param int enrolment_id
	 * @return Module object
	 */
	public function getModuleByModuleIdCourseCodeAndEnrolmentId($module_id, $course_code, $enrolment_id)
	{
		$module = new Module($module_id);
		$details = $this->getModuleDetails($module_id, $course_code);
		$module->setModuleName($details['name']);
		$module->setModuleDescription($details['description']);
		$module->setModerated(shouldBeBoolean($details['moderated']));
		$link = new Link($details['name'], $details['description'], $details['url']);
		$module->setLink($link);
		$enrolment_details = $this->getEnrolmentDetails($enrolment_id);
		$completion_time = $this->getCompletionByModuleAndStudent($course_code, $module_id, $enrolment_details['student_id']);
		if ($completion_time)
		{
			$module->setCompleted(true, $completion_time);
		}
		return $module;
	}

	/**
	 * findChildCourses
	 * @param string $course_code
	 * @return array(string) array of course codes which are children to $course_code
	 */
	public function findChildCourses($course_code)
	{	
		$child_courses = $this->suck('sc_course', 'course_code', array('parent_course_code' => $course_code), 'course_code');
	  	return $child_courses;
	}

	/**
	 * findParentCourse
	 * @param string course_code to query
	 * @return string course code of parent_course, or false if the course has no parent
	 */
	public function findParentCourse($course_code)
	{
		$parent_course = $this->pluck('sc_course', null, array('course_code' => $course_code), 'parent_course_code');
		if ($parent_course !== false)
		{
			return $parent_course['parent_course_code'];
		}
		else
		{
			return $parent_course;
		}
	}

	/**
	 * getCourses
	 * lists all courses
	 * @param boolean toplevel only defaults to true
	 * @return array string => string course_code => course_name
	 */
	public function getCourses($toplevel = true)
	{
		$result = array();
		if ($toplevel == false)
		{
			$retrieved = $this->suck('sc_course', array('course_code', 'course_name'));
			foreach ($retrieved as $row)
			{
				$code = $row['course_code'];
				$name = $row['course_name'];
				$result[$code] = $name;
			}
		}
		else
		{
			$sql = 'select course_code, course_name from sc_course where parent_course_code is null';
			$retrieved = $this->retrieve($sql);
			$retrieved->setFormat("ASSOC");
			if ($retrieved->getNumRows() > 0)
			{
				foreach ($retrieved as $row)
				{
					$result[$row->offsetGet('course_code')] = $row->offsetGet('course_name');
				}
			}
		}
		return $result;
	}

	/**
	 * setCourseAvailability
	 * allows a course's 'active' attribute to be set to true or false, along with those of any sub-courses
	 * An unavailable course will have various effects on enrolment, staff warnings and possibly submission
	 * @param string $course_code, the code of the course to change availability for.
	 * A course code with children will change the status of its children too.
	 * @param boolean $is_available, default true
	 * @return boolean true if the availability was changed successfully
	 */
 	public function setCourseAvailability($course_code, $is_available = true)
	{
		$changed_successfully = false;
		if (is_bool($is_available) and ! empty($course_code))
		{
			$child_courses = $this->findChildCourses($course_code);
			$transaction_log = array();
			$this->transaction('begin');
			$transaction_log[] = $this->updateTableItem('sc_course', null, array('active' => $is_available), array('course_code' => $course_code));
			if ($child_courses !== false)
			{
				foreach ($child_courses as $module)
				{
					$transaction_log[] = $this->updateTableItem('sc_course', null, array('active' => $is_available), array('course_code' => $module));
				}	
			}
			if ($this->assessTransaction($transaction_log))
			{
				$changed_successfully = true;
			}
		}
		return $changed_successfully;
	}
	/**
	 * getEnrolmentSummary
	 * returns a list of all modules and courses across the enrolments.
	 * in the form of a nested array [course_code][module]
	 * Depending on the input list, this function can generate lists of completed, evaluated
	 * or other enrolments.
	 * Used for evaluation of short courses
	 * (Originally from airport gate5/evaluation/
	 * @param array(int) list of enrolment_ids
	 * @return array[string] => array(string) 
	 * eg. course 1 => (module 1, module 2), course 2 => (module 3, module 4) etc.
	 */
	public function getEnrolmentSummary($enrolment_list)
	{
		$parent_courses = array();
		$modules_evaluated = array();
		foreach ($enrolment_list as $enrolment_id)
		{	
			$courses = $this->getCoursesByEnrolment($enrolment_id);
			$modules_evaluated = array_merge($modules_evaluated, $courses);
		}
		$modules_evaluated = array_unique($modules_evaluated);
		foreach ($modules_evaluated as $module)
		{
			$parent_course = $this->findParentCourse($module);
			if (in_array($parent_course, array_keys($parent_courses)))
			{
				$parent_courses[$parent_course][] = $module;
			}
			else
			{
				$parent_courses[$parent_course] = array($module);
			}
		}
		return $parent_courses;
	}

	/**
	 * getEnrolmentByStudentId
	 * @param string $course_code, the course code
	 * @param string $student_id, the student id code
	 * If no student id then course status is unknown
	 * If no course code then course status is non-existent 
	 * @return Enrolment object
	 */
	public function getEnrolmentByStudentId($course_code, $student_id)
	{
		$sc_enrolment = false;
		$sc_signup = $this->pluck('sc_signup', null, array('course_code' => $course_code, 'student_id' => $student_id));
		$student = $this->getStudentDetails($student_id);
		if ($sc_signup)
		{
			$enrolment_id = $sc_signup['enrolment_id'];
			$sc_enrolment = $this->pluck('sc_enrolment_view', null, array('enrolment_id' => $enrolment_id));
			$tutor = $this->pluck('tutor', null, array('tutor_id' => $sc_enrolment['tutor_id']));
		}
		$enrolment = new Enrolment($student);
		if ($sc_enrolment)
		{
			// enrolled
			$enrolment->setID($enrolment_id);
			$begin = $enrolment->addDate(strtotime($sc_enrolment['start_date']), 'begin');
			$end = $enrolment->addDate(strtotime($sc_enrolment['end_date']), 'end');
			$modules = $this->getModulesByCourseCode($course_code);
			$next_module_link = null;
			$completed = true;
			$active = true;
			// work out if expired, and then completed
			$current_time = time();
			if ($end < $current_time)
			{
				//expired
				$active = false;
			}
			$last_timestamp = 0;
			$all_modules = array();
			foreach ($modules as $module)
			{
				$module_instance = new Module($module);
				//$module_details = $this->getModuleDetails($module);
				$module_completed = $this->getCompletionByModuleAndStudent($course_code, $module, $student_id);
				$link_dao = new LinkDao(DBCONNECT);
				$incomplete_module = $this->pluck('sc_course_module', null, array('module_id' => $module, 'course_code' => $course_code), 'link');
				$next_module_link = $link_dao->getLinkByKeyword($incomplete_module['link']);
				$module_instance->setLink($next_module_link);

				if ($module_completed === false)
				{
					$module_instance->setCompleted(false, null);
					$completed = false; // if any module is not complete, neither is course
				}
				else
				{
					if ($module_completed > $last_timestamp)
					{
						$last_timestamp = $module_completed;
					}
					$module_instance->setCompleted(true, $module_completed);
				}
				$all_modules[] = $module_instance;
			}
			if ($completed)
			{
				//show last timestamp
				$last_submitted = $enrolment->addDate($last_timestamp, 'last_submitted');
				$enrolment_status = Enrolment::PREVIOUS_ENROLMENT;
				$enrolment->setCompleted(true, $last_submitted);
				//evaluation link here
			}
			else
			{
				if ($active === true)
				{
					$enrolment_status = Enrolment::ACTIVE_ENROLMENT;
					//show link to next activity
				}
				else
				{
					$enrolment_status = Enrolment::PREVIOUS_ENROLMENT;
					//evaluation2 link here
					
				}
				
				//show expiry date
			}
			if ($enrolment_status == Enrolment::PREVIOUS_ENROLMENT)
			{
				//  check if prohibited
				$enrolment_semester = $enrolment->calculateSemester($begin);
				$this_semester = $enrolment->calculateSemester('today');
				if ($enrolment_semester == $this_semester)
				{
					$enrolment_status = Enrolment::PROHIBIT_ENROLMENT;
				}
			}
			$enrolment->setSupplementalStatus('evaluated', shouldBeBoolean($sc_enrolment['evaluated']));
			$enrolment->setSupplementalStatus('evaluation_suggested', shouldBeBoolean($sc_enrolment['evaluation_suggested']));
			$enrolment->setEnrolmentStatus($enrolment_status);
			$enrolment->setTutor($tutor);
			$enrolment->setModules($all_modules);
		}
		else
		{
			$enrolment_status = Enrolment::NO_ENROLMENT;
			//not enrolled -- signed up, or recommended?
			if ($sc_signup and isset($sc_signup['signup_date']))
			{
				$signup_date = $enrolment->addDate(strtotime($sc_signup['signup_date']), 'signup');
			}
			if (shouldBeBoolean($sc_signup['recommended']))
			{
				$enrolment->setSupplementalStatus('recommended', true);
			}
		}
		return $enrolment;
	}

	/**
	 * returns all of the enrolment_ids associated with a particular student
	 * @param string $student_id
	 * @param string $parent_course, the course_code of the top-level course to screen for, eg. '5_writing'
	 * for the gate 5 writing course
	 * @param boolean $completed_only default null.
	 * If true, returns only completed enrolments; if false returns only incomplete 
	 * (ie. time expired but modules outstanding) enrolments
	 * @param boolean $unevaluated_only default null. If true, returns only enrolments which have NOT been
	 * evaluated. If false, returns only those which HAVE been evaluated
	 * @return array(int) of enrolment_ids
	 */
	public function getEnrolmentsByStudent($student_id, $parent_course = null, $completed_only = null, $unevaluated_only = null)
	{
		if (isset($parent_course))
		{
			$parent_course_insert = ", sc_course c where c.course_code = s.course_code and c.parent_course_code = ". $this->da->escapeContext($parent_course) ." and";
		}
		else
		{
			$parent_course_insert = 'where';
		}
		$sql = "select e.enrolment_id as enrolment_id from sc_enrolment e, sc_signup s $parent_course_insert s.student_id = " . $this->da->escapeContext($student_id) . " and e.enrolment_id = s.enrolment_id order by e.start_date";
		$result = $this->retrieve($sql);
		$enrolments = $this->processMulticolumnResults($result, 'enrolment_id');
		if ($completed_only == true)
		{
			$enrolments = array_filter($enrolments, array($this, 'getEnrolmentComplete'));
		}
		else if ($completed_only === false)
		{
			$enrolments = array_filter($enrolments, array($this, 'getEnrolmentIncomplete'));
		}
		$evaluated = array_filter($enrolments, array($this, 'getEnrolmentEvaluated'));
		if ($unevaluated_only == true)
		{
			$enrolments = array_diff($enrolments, $evaluated);
		}
		else if ($unevaluated_only === false)
		{
			$enrolments = $evaluated;
		}
		return $enrolments;
	}

	/**
	 * getStudentDetails
	 * returns an array of student details for the given user name, or the session login details if there
	 * is no entry in the student table
	 * @param string username
	 * @return array(student_id => string, given_name => string, family_name => string, email => string) 
	 */
	public function getStudentDetails($student_id)
	{
		$student = array();
		$student = $this->pluck('student', null, array('student_id' => $student_id));
		$attributes = array('student_id'=>'username', 'given_name'=>'given_names', 'family_name'=>'family_name', 'email'=>'email');
		foreach ($attributes as $attribute => $alternate)
		{
			if (! isset($student[$attribute]))
			{
				$student[$attribute] = $_SESSION[$alternate];
			}
		}
		return $student;
	}

	/**
	 * getManagementList
	 * retrieves the list of ids of management staff
	 * @param string $course_code (optional), if included, 
	 * retrieves only management staff associated with a particular course
	 * @return array(string) list of $management user ids
	 */
	public function getManagementList($course_code = null)
	{
		$management_list = array();
		$table = 'sc_management';
		$column = 'management_id';
		if (! empty($course_code))
		{
			$constraint = array('course_code' => $course_code);
			$sql = "select $column from $table intersect select $column from sc_management_course where course_code = ". $this->da->escapeContext($course_code);
			$result = $this->retrieve($sql);
			$management_list = $this->processMulticolumnResults($result, $column);
		}
		else
		{
			$management_list = $this->suck($table, $column);
		}
		return $management_list;
	}

	/**
	 * getManagementDetails
	 * retrieves details about management staff from id
	 * @param string username
	 * @return array(student_id => string, given_name => string, family_name => string, email => string) 
	 */
	public function getManagementDetails($username)
	{
		$management = $this->pluck('sc_management', null, array('management_id' => $username));
		return $management;
	}

	/**
	 * getManagementEmail
	 * a subset of the above function. Only returns the email of the manager
	 * ironically, it's a bigger function
	 * similar in structure to getCourseName
	 * (Oh, for Common Lisp macros!)
	 * @param string (8 chars) $management_id
	 * @return string, the email only
	 */
	public function getManagementEmail($management_id)
	{
		$result = null;
		$details = $this->getManagementDetails($management_id);
		if (isset($details['email']))
		{
			$result = $details['email'];
		}
		return $result;
	}

	/**
	 * isStudent
	 * related to getStudentDetails but breaks less stuff
	 * @param string $username
	 * @return boolean true if the username is present in the student table
	 */
	public function isStudent($username)
	{
		$presence = false;
		$student = $this->pluck('student', null, array('student_id' => $username), 'student_id');
		if (isset($student['student_id']))
		{
			$presence = true;
		}
		return $presence;
	}

	/**
	 * isAdmin
	 * @return boolean true if the username is present in the admin table
	 */
	public function isAdmin()
	{
		$presence = false;
		$management = $this->pluck('sc_management', null, array('management_id' => $_SESSION["username"]), 'management_id');
		if (isset($management['management_id']))
		{
			$presence = true;
		}
		return $presence;
	}

	/**
	 * isTutor
	 * @return boolean true if the current user is present in the tutor table
	 */
	public function isTutor()
	{
		$presence = false;
		$tutor = $this->pluck('tutor', null, array('tutor_id' => $_SESSION["username"]), 'tutor_id');
		if (isset($tutor['tutor_id']))
		{
			$presence = true;
		}
		return $presence;
	}

	/** getTutorDetails
	 *
	 * get tutor details
	 * @param string username
	 * @return array of given_names, family_names, email, active status
	 */
	public function getTutorDetails($username)
	{
		return $this->pluck('tutor', $username);
	}

	/**
	 * getAvailableTutors
	 *
	 * @param bool check for active status
	 * @return array of tutors
	 */
	public function getAvailableTutors($active = true)
	{
		$tutors = array();
		if ($active == true)
		{
			$tutor_ids = $this->suck('tutor', 'tutor_id', array('active' => 'y'), 'given_name');
		}
		else
		{
			$tutor_ids = $this->suck('tutor', 'tutor_id', null, 'given_name');
		}
		return $tutor_ids;
	}

	/**
	 * getTutorsHours
	 *
	 * get all tutors hours sorted by course
	 * @return array of the tutors' details including hours allocated grouped into courses
	 */
	public function getTutorsHours($active = true)
	{
		$tutors = array();
		$insert = null;
		if ($active == true)
		{
			$insert = ", tutor t where active = 'y' and t.tutor_id = s.tutor_id";
		}
		$sql = "select s.* from sc_tutor_course s$insert order by course_code, hours_allocated";
		$results = $this->retrieve($sql);
		$results->setFormat("ASSOC");
		while ($result = $results->getRow())
		{
			$course = $result['course_code'];
			$new_tutor = $this->getTutorDetails($result['tutor_id']);
			$new_tutor['hours_allocated'] = $result['hours_allocated'];
			if (!array_key_exists($course, $tutors))
			{
				$tutors[$course] = array();
			}
			$tutors[$course][] = $new_tutor;
		}
		return $tutors;
	}

	/**
	 * updateTutorHours
	 * @param string tutor_id
	 * @param string course_code
	 * @param numeric $new_hours
	 */
	public function updateTutorHours($tutor_id, $course_code, $new_hours)
	{
		$hours_allocated = $new_hours;
		return $this->updateTableItem('sc_tutor_course', null, array('hours_allocated'=> $hours_allocated), array('tutor_id'=>$tutor_id, 'course_code' => $course_code));
	}

	/**
	 * insertTutorHours
	 * @param string tutor_id
	 * @param numeric $new_hours
	 * @todo combine these two functions?
	 */
	public function insertTutorHours($tutor_id, $course_code, $new_hours=0)
	{
		$hours_allocated = $new_hours;
		return $this->insertTableItem('sc_tutor_course', array('tutor_id'=>$tutor_id, 'course_code' => $course_code, 'hours_allocated' =>$hours_allocated));
	}

	/**
	 * setTutorActiveStatus
	 *
	 * @param string tutor_id
	 * @param char status
	 */
	public function setTutorActiveStatus($tutor_id, $status)
	{
		return $this->updateTableItem('tutor', null, array('active' => $status), array('tutor_id' => $tutor_id));
	}

	/**
	/**
	 * addTutor
	 *
	 * @param string tutor_id
	 * @param string given_name
	 * @param string family_name
	 */
	public function addTutor($tutor_id, $given_name, $family_name)
	{
		$added_successfully = false;
		$transaction_log = array();
		$this->transaction('begin');
		$transaction_log[] = $this->insertTableItem('tutor', array('tutor_id' => $tutor_id, 'given_name' => $given_name, 'family_name' => $family_name, 'email' => $tutor_id . '@unimelb.edu.au', 'active' => 'y'));
		if ($this->assessTransaction($transaction_log))
		{
			$added_successfully = true;
		}
		return $added_successfully;
	}

	/**
	 * getEnrolmentsByTutor
	 *
	 * @param string tutor_id
	 * @return array(Enrolment objects)
	 */
	public function getEnrolmentsByTutor($tutor_id = 'all_tutors')
	{
		$enrolments = array();
		if ($tutor_id == 'all_tutors')
		{
			$enrolments = $this->suck('sc_enrolment_view', '*');
		}
		else
		{
			$enrolments = $this->suck('sc_enrolment_view', '*', array('tutor_id' => $tutor_id));
		}
		$enrolment_objects = array();
		foreach ($enrolments as $enrolment)
		{
			$student_id = $enrolment['student_id'];
			$course_codes = $this->getCoursesByEnrolment($enrolment['enrolment_id']);
			foreach ($course_codes as $course_code)
			{
				$enrolment_objects[] = $this->getEnrolmentByStudentId($course_code, $student_id);
			}
		}
		return $enrolment_objects;
	}

	/**
	 * getTutorHours
	 *
	 * @return array mixed false or numeric
	 */
	public function getTutorHours($username, $course)
	{
		return $this->pluck('sc_tutor_course', 'hours_allocated', array('tutor_id' => $username, 'course_code' => $course));
	}

	/**
	 * getNextTutor
	 *
	 * search for the tutor with the highest number of hours remaining
	 * for the particular course
	 * @param string course_code, should be a top-level course code
	 * @param float, minimum no. of assessment hours. Default is 15mins, as every enrolment
	 * requires this much as a base. However the minimum will almost always be higher than this.
	 * @return array of the tutor's details or false if there are no tutors available
	 * also adds the time remaining to the tutor array
	 * If multiple tutors have the same amount of time allocated, picks randomly among them
	 */
	public function getNextTutor($course_code, $time_required = 0.25)
	{
		$tutor = false;
		$sql = "select t.tutor_id, s.hours_allocated from tutor t, sc_tutor_course s where s.course_code = " . $this->da->escapeContext($course_code) . " and s.hours_allocated > " . $this->da->escapeContext($time_required) . " and t.active = 'y' and t.tutor_id = s.tutor_id order by s.hours_allocated desc";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		if ($result->getNumRows() > 0)
		{
			if ($result->getNumRows() == 1)
			{
				$row = $result->getRow();
				$time_remaining = $row['hours_allocated'] - $time_required;
				$tutor = $this->getTutorDetails($row['tutor_id']);
			}
			else
			{
				$possible_tutors = array();
				$row = $result->getRow();
				$hours_allocated = $row['hours_allocated'];
				$possible_tutors[] = $row;
				while ($tutor_row = $result->getRow())
				{
					$other_hours_allocated = $tutor_row['hours_allocated'];
					if ($other_hours_allocated == $hours_allocated)
					{
						$possible_tutors[] = $tutor_row;
					}
				}
				rearrange($possible_tutors);
				$possible_tutors = array_values($possible_tutors);
				$time_remaining = $possible_tutors[0]['hours_allocated'] - $time_required;
				$tutor = $this->getTutorDetails($possible_tutors[0]['tutor_id']);
			}
			//calculate remaining time for this tutor
			$tutor['time_required'] = $time_required;
			$tutor['time_remaining'] = $time_remaining;
		}
		return $tutor;
	}

	/**
	 * returns no. of students and no. of hours allocated between
	 * the two dates.
	 * @param string $tutor_id
	 * @param string $latest the latest date, defaults to now. 
	 * Input format otherwise ISO 8601: eg. 1999-01-08 for 8th January 1999 , though
	 * '08-Jan-1999' is also unambiguous. 
	 * @param string $earliest, defaults to a week ago, otherwise input as above
	 * @return array(enrolments=>, students=>, hours_added =>)
	 */
	public function getTutorSummary($tutor_id, $earliest="default", $latest="default")
	{
		$time_added = 0;
		$students = 0;
		$enrolments = 0;
		if ($earliest == "default")
		{
			$earliest = "now() - interval '1 week'";
		}
		else
		{
			$earliest = "'$earliest'";
		}
		if ($latest == "default")
		{
			$latest = 'now()';
		}
		else
		{
			$latest = "'$latest'";
		}
		$sql = "select enrolment_id, student_id from sc_enrolment_view where tutor_id = " . $this->da->escapeContext($tutor_id) . " and start_date > $earliest and start_date < $latest";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$enrolment_ids = array();
		$student_ids = array();
		while ($enrolment_row = $result->getRow())
		{
			$enrolment_ids[] = $enrolment_row[0];
			$student_ids[] = $enrolment_row[1];
		}
		$students = count(array_unique($student_ids));
		$enrolments = count($enrolment_ids);
		foreach ($enrolment_ids as $enrolment_id)
		{
			$course_codes = $this->getCoursesByEnrolment($enrolment_id);
			$time_added = $time_added + $this->findTimeRequired($course_codes);
		}
		$output = array(
			'enrolments' => $enrolments,
			'students' => $students,
			'hours_added' => $time_added
			);
		return $output;
	}

	/**
	 * findTimeRequired
	 * Takes a list of courses and finds the time required for tutor assessment
	 * @param array(string) list of course_codes. The codes should all have the same parent
	 * @return float, time in hours required to assess the course. 15mins (0.25) is added to
	 * the time required, along with the module requirements
	 * This function is used in conjunction with getNextTutor
	 */
	public function findTimeRequired($course_codes)
	{
		$all_modules = array();
		$time_required = 0.25;
		if (is_array($course_codes))
		{
			foreach ($course_codes as $course)
			{
				$all_modules = array_merge($all_modules, $this->getModulesByCourseCode($course));
			}
			if (count($all_modules) > 0)
			{
				$all_modules = array_map(array($this->da, 'escapeContext'), $all_modules);
				$where_clause = $this->makeConstraintSQL(array('module_id' => $all_modules), 'sc_module', $operator_requested = 'equal');
				$sql = "SELECT sum(assessment_hours) from sc_module WHERE $where_clause";
				$result = $this->retrieve($sql);
				$result->setFormat("NUM");
				if ($result->getNumRows() == 1) 
				{
					$row = $result->getRow();
					$time_required += $row[0];
				}
			}
			else
			{
				$time_required = 0;
			}
		}
		return $time_required;
	}

	/**
	 * addEnrolment
	 * insert enrolment information into the database
	 * This function calls others to allocate a tutor, create an enrolment,
	 * create a signup for each course code if one does not already exist
	 * and allocate the enrolment_id to each of the signups.
	 * In the process of doing so it will initiate various emails (using ShortCourseEmailDispatcher),
	 * deduct the required time from the tutor's allocated hours,
	 * and check the status of the allocated hours.
	 * @param string $student_id, obtained from login
	 * @param array(string) $course_codes, usually obtained from signup page
	 * Note that a collection of course_codes passed to this function
	 * should share parent course code, or be a single course code with no parent
	 * @return boolean true if the enrolment was added successfully (or if no signups were added
	 * but things otherwise went smoothly) otherwise returns an error code as follows:
	 *	1: tutor unavailable
	 *	2: non-student login
	 *	3: database error
	 */
	public function addEnrolment($student_id, $course_codes)
	{
		$error_code = null;
		$success = false;
		$signup_updates = array();
		$signup_creates = array();
		$recommended_updates = array();
		$mailbag = array();
		$is_student = $this->isStudent($student_id);
		if (! $is_student)
		{
			$error_code = 2;
		}
		if (is_array($course_codes) and $is_student)
		{
			// choose tutor
			$time_required = $this->findTimeRequired($course_codes);
			$primary_course_code = "";
			foreach ($course_codes as $cc)
			{
				$enrolment = $this->getEnrolmentByStudentId($cc, $student_id);
				if ($enrolment->getEnrolmentStatus() == Enrolment::NO_ENROLMENT)
				{
					if ($enrolment->isSignedUp() or $enrolment->getRecommended())
					{
						$signup_updates[] = $cc;
						if ($enrolment->getRecommended())
						{
							$recommended_updates[] = $cc;
						}
					}
					else
					{
						$signup_creates[] = $cc;
					}
				}
			}
			$primary_course_code = $this->findParentCourse($course_codes[0]);
			if ($primary_course_code == false)
			{
				$primary_course_code = $course_codes[0];
			}
			$course_details = $this->getCourseDetails($primary_course_code);
			$tutor = $this->getNextTutor($primary_course_code, $time_required);
			$tutor_id = $tutor['tutor_id'];
			// create enrolment entry
			$transaction_log = array();
			$this->transaction('begin');
			if (! empty($tutor_id))
			{
				$transaction_log[] = $this->insertTableItem('sc_enrolment', array('tutor_id' => $tutor_id, 'start_date'=>'localtimestamp'));
				$enrolment_id = $this->getLastID('sc_enrolment');
				$transaction_log[] = $this->updateTableItem('sc_tutor_course', null, array('hours_allocated' => $tutor['time_remaining']), array('tutor_id' => $tutor_id, 'course_code' => $primary_course_code));
				foreach ($signup_creates as $new_course_code)
				{
					$transaction_log[] = $this->insertTableItem('sc_signup', array('student_id' => $student_id, 'course_code' => $new_course_code, 'signup_date' => 'localtimestamp', 'enrolment_id' => $enrolment_id));
				}
				foreach ($signup_updates as $updated_course_code)
				{
					$update_fields = array('enrolment_id' => $enrolment_id);
					if (in_array($updated_course_code, $recommended_updates))
					{
						$update_fields['signup_date'] = 'localtimestamp';
					}
					$transaction_log[] = $this->updateTableItem('sc_signup', null, $update_fields, array('student_id' => $student_id, 'course_code' => $updated_course_code));
				}
				//don't add enrolment if we haven't changed anything
				//still counts as a 'success' otherwise.
				if (count($signup_updates) == 0 and count($signup_creates) == 0)
				{
					$transaction_log[] = false;
					$success = true;
				}
			}
			else
			{
				$transaction_log[] = false;
				$error_code = 1;
			}
			if ($this->assessTransaction($transaction_log))
			{
				$success = true;
				// send emails
				$minutes_allocated = round($tutor['time_required'] * 60, 0);
				$extra_text_enrolment_tutor = "You have been allocated $minutes_allocated minutes to provide feedback to this student.\n\nThanks";
				$mailbag[] = new ShortCourseEmailDispatcher('enrolment-tutor', $enrolment_id, $extra_text_enrolment_tutor);
				$mailbag[] = new ShortCourseEmailDispatcher('enrolment-student', $enrolment_id);
			}
			else
			{
				// update signup times for new signups, without enrolment info
				// all signups will have the same date by virtue of the BEGIN..END block
				$transaction_log = array();
				$this->transaction('begin');
				foreach ($signup_creates as $new_course_code)
				{
					$transaction_log[] = $this->insertTableItem('sc_signup', array('student_id' => $student_id, 'course_code' => $new_course_code, 'signup_date' => 'localtimestamp'));
				}
				$student_details = $this->getStudentDetails($student_id);
				if (count($signup_creates) != 0 and shouldBeBoolean($course_details['active']) === true)
				{
					$no_enrolment_mail = new ShortCourseEmailDispatcher('no-enrolment-student', null, "", $primary_course_code);
					$no_enrolment_mail->changeRecipient($student_details['email'], $student_details['given_name']);
					$mailbag[] = $no_enrolment_mail;
				}
				if ($this->assessTransaction($transaction_log) == false)
				{
					//there was a problem accessing the database
					$error_code = 3;
				}
				else
				{
					$error_code = 1;
				}
			}
		}
		//housekeeping -- only send warning emails for active courses
		if (isset($course_details) and shouldBeBoolean($course_details['active']) === true)
		{
			if ($this->checkTutorHours($primary_course_code, 8))
			{
				$mailbag[] = new ShortCourseEmailDispatcher('tutor-hours-management', null, "", $primary_course_code);
			}
			if ($this->checkWaitingStudents(10))
			{
				$mailbag[] = new ShortCourseEmailDispatcher('students-waiting-management', null, "", $primary_course_code);
			}
		}
		mailbag($mailbag);
		if (! is_null($error_code))
		{
			$success = $error_code;
		}
		return $success;
	}

	/**
	 * removeEnrolment
	 * removes an enrolment from the database
	 * @param int $enrolment_id
	 * @return true if successful
	 */
	public function removeEnrolment($enrolment_id)
	{
		$success = false;
		$transaction_log = array();
		if (is_numeric($enrolment_id))
		{
			$enrolment_basics = $this->pluck('sc_enrolment_view', null, array('enrolment_id' => $enrolment_id));
			$courses = $this->getCoursesByEnrolment($enrolment_id);
			$parent_course = $this->findParentCourse($courses[0]);
			$current_tutor_time = $this->getTutorHours($enrolment_basics['tutor_id'], $parent_course);
			$course_time_required = $this->findTimeRequired($courses) - 0.25;
			$new_tutor_time = $current_tutor_time['hours_allocated'] + $course_time_required;
			$this->transaction('begin');
			$transaction_log[] = $this->removeSubmission($enrolment_id, 'all');
			$transaction_log[] = $this->deleteTableItem('sc_signup', null, array('enrolment_id' => $enrolment_id));
			$transaction_log[] = $this->deleteTableItem('sc_enrolment', null, array('enrolment_id' => $enrolment_id));
			$transaction_log[] = $this->updateTutorHours($enrolment_basics['tutor_id'], $parent_course, $new_tutor_time);
			if ($this->assessTransaction($transaction_log))
			{
				$success = true;
			}
		}
		return $success;
	}
	/**
	 * extendEnrolment
	 * extends an enrolment by x days
	 * @param int $enrolment_id
	 * @param int $days
	 * @return true if successful
	 */
	public function extendEnrolment($enrolment_id, $days)
	{
		$success = false;
		if (is_numeric($enrolment_id) and is_numeric($days))
		{
			$current_days = $this->pluck('sc_enrolment', $enrolment_id, null, 'extra_days');
			$this->transaction('begin');
			$transaction_log[] = $this->updateTableItem('sc_enrolment', $enrolment_id, array('extra_days' => $current_days['extra_days'] + $days));
			if ($this->assessTransaction($transaction_log))
			{
				$success = true;
			}
		}
		return $success;
	}

	/**
	 * checkTutorHours
	 * @param string $course_code, the top-level course code to check
	 * (eg. 5_writing)
	 * @param int minimum hours to check, default 8
	 * @return boolean true if less than minimum hours available to all tutors
	 * NOTE that the sc_tutor_course table should only have top-level course code
	 * as course_code attribute, and this function does not currently link related
	 * course codes (though it could with findParent/child functions above).
	 */
	public function checkTutorHours($course_code, $min_hours = 8)
	{
		if (! is_numeric($min_hours))
		{
			throw new Exception("Minimum hours must be a number.");
		}
		$hours_warning = false;
		$sql = "select sum(hours_allocated) as total_hours from sc_tutor_course where course_code =" . $this->da->escapeContext($course_code);
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		if ($result->getNumRows() == 1)
		{
			$row = $result->getRow();
			$total_hours = $row->offsetGet(0);
			if ($total_hours < $min_hours)
			{
				$hours_warning = true;
			}
		}
		return $hours_warning;
	}
	
	/**
	 * checkWaitingStudents
	 * @param int maximum no. of acceptable students on waiting list
	 * (ie. signed up with no tutor allocated), default 10
	 * @return boolean if more students than the max are waiting
	 */
	public function checkWaitingStudents($max_students = 10)
	{
		$waiting_warning = false;
		$waiting = $this->getWaitingStudents();
		if (count($waiting) > $max_students)
		{
			$waiting_warning = true;
		}
		return $waiting_warning;
	}

	/**
	 * getWaitingStudents
	 * returns the list of students who are waiting for a tutor, ordered by signup date
	 * That is, those who have a signup date but no enrolment_id in sc_signup
	 * @return array(string) list of student_ids
	 */
	public function getWaitingStudents()
	{
		$sql = "select student_id from sc_signup where enrolment_id is null and signup_date is not null order by signup_date";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$student_list = array();
		if ($result->getNumRows())
		{
			foreach($result as $row)
			{
				$student_list[] = $row->offsetGet(0);
			}
		}
		return array_unique($student_list);
	}

	/**
	 * enrolWaitingStudents
	 * attempts to enrol students on the waiting list
	 * using $this->addEnrolment
	 * This function is usually triggered by adding tutor hours
	 * @return int no. of students remaining on waiting list
	 */
	public function enrolWaitingStudents()
	{
		$waiting_students = $this->getWaitingStudents();
		foreach ($waiting_students as $student)
		{
			$courses = $this->suck('sc_signup', 'course_code', array('student_id' => $student));
			$enrolled = $this->addEnrolment($student, $courses);
			if ($enrolled !== true)
			{
				break;
			}
		}
		$waiting_students = $this->getWaitingStudents();
		$waiters = count($waiting_students);
		return $waiters;
	}

	/**
	 * checkDaysWaiting
	 * checks to see if a student has been on the waiting list longer than a specified no. of days
	 * @param string $student_id
	 * @param int $max_days, maximum number of days allowable wait before this function returns true
	 * a positive integer
	 * @return boolean TRUE if the student has been waiting longer than the maximum no. of days
	 */
	public function checkDaysWaiting($student_id, $max_days)
	{
		$more_than_max = false;
		$waiting_students = $this->getWaitingStudents();
		if (in_array($student_id, $waiting_students))
		{
			// possibly pgsql only sql below!
			$sql = "select now() - min(signup_date) from sc_signup where student_id = " . $this->da->escapeContext($student_id);
			$result = $this->retrieve($sql);
			$result->setFormat("NUM");
			if ($result->getNumRows() == 1)
			{
				$row = $result->getRow();
				$interval = $row->offsetGet(0);
				$days_elapsed = getDaysFromInterval($interval);
				if ($days_elapsed > $max_days)
				{
					$more_than_max = true;
				}
			}
		}
		return $more_than_max;
	}

	/**
	 * checkExpiryProximity
	 * checks to see if an enrolment is closer than x days to expiry
	 * @param int $enrolment_id
	 * @param int $max_days, number of days until expiry (zero for expiry date itself)
	 * @param boolean $exact, default FALSE. If true, tests for exactly the number of days,
	 * eg, exactly 10 days rather than 10 or fewer days. Set to true if you only want this
	 * to be triggered once.
	 * @return boolean TRUE if the number of days until expiry is less than $max days
	 */
	public function checkExpiryProximity($enrolment_id, $max_days, $exact = false)
	{
		$no_time_left = false;
		if (is_numeric($enrolment_id))
		{
			$enrolment_view = $this->pluck('sc_enrolment_view', null, array('enrolment_id'=>$enrolment_id), 'time_remaining');
			$days_left = getDaysFromInterval($enrolment_view['time_remaining']);
			if ($exact === true)
			{
				//print "<pre>days left: $days_left, max_days: $max_days</pre>"; //test?
				if (round($days_left) == $max_days)
				{
					$no_time_left = true;
				}
			}
			else
			{
				if ($days_left < $max_days)
				{
					$no_time_left = true;
				}
			}
		}
		return $no_time_left;
	}

	/**
	 * getCurrentEnrolments
	 * @return a list of enrolment_ids which have not expired
	 */
	public function getCurrentEnrolments()
	{
		$enrolment_ids = array();
		$enrolment_times = $this->suck('sc_enrolment_view', '*', null, array('enrolment_id', 'time_remaining'));
		foreach ($enrolment_times as $enrolment)
		{
			if (getDaysFromInterval($enrolment['time_remaining']) > 0)
			{
				$enrolment_ids[] = $enrolment['enrolment_id'];
			}
		}
		return $enrolment_ids;
	}

	/**
	 * getUnnotifiedEnrolments
	 * returns a list of enrolment_ids for those who have neither evaluated nor been told to
	 * evaluate their courses
	 * @return array(int), a list of enrolment_ids which have not been suggested to evaluate their courses
	 */
	public function getUnnotifiedEnrolments()
	{
		$unnotified = $this->suck('sc_enrolment', 'enrolment_id', array('evaluated'=>false, 'evaluation_suggested' => false));
		return $unnotified;
	}

	/**
	 * setNotified
	 * sets the evaluation_suggested to $veracity (default true);
	 * @return truth state of evaluation_suggested
	 */
	public function setNotified($enrolment_id, $veracity = true)
	{
		$result = false;
		if ($veracity !== true)
		{
			$veracity = false;
		}
		if (is_numeric($enrolment_id))
		{
			$result = $this->updateTableItem('sc_enrolment', $enrolment_id, array('evaluation_suggested' => $veracity));
		}
		return $result;
	}

	/**
	 * setEvaluated, like setNotified above
	 * sets the evaluated attribute to $veracity (default true);
	 * @return truth state of evaluation_suggested
	 */
	public function setEvaluated($enrolment_id, $veracity = true)
	{
		$result = false;
		if ($veracity !== true)
		{
			$veracity = false;
		}
		if (is_numeric($enrolment_id))
		{
			$result = $this->updateTableItem('sc_enrolment', $enrolment_id, array('evaluated' => $veracity));
		}
		return $result;
	}

	/**
	 * setRecommended
	 * similar to above, but works on sc_signup table
	 * sets a recommended tag to the particular courses for the student, if not already there
	 * @param string $student_id
	 * @param array(string) $course_codes
	 * @param boolean $veracity default = true
	 * @todo needs to insert if the course-code/student combo doesn't already exist or update
	 */
	public function setRecommended($student_id, $course_codes, $veracity=true)
	 {
	 	$result = false;
		if ($veracity !== true)
		{
			$veracity = false;
		}
		if ($this->isStudent($student_id))
		{
			foreach ($course_codes as $code)
			{
				if ($this->pluck('sc_signup', null, array('student_id' => $student_id, 'course_code' =>$code)) != false)
				{
					$result = $this->updateTableItem('sc_signup', null, array('recommended' =>$veracity), array('student_id' => $student_id, 'course_code' => $code));
				}
				else
				{
					$result = $this->insertTableItem('sc_signup', array('student_id' => $student_id, 'course_code' =>$code, 'recommended' =>$veracity));
				}
			}
		}
		return $result;
	 }

	/**
	 * removeExpiredSignups
	 * deletes unenrolled signup dates from the database after a set time
	 * (default is 12 days)
	 * @param int $max_days
	 * @return database result (FALSE if unsuccessful)
	 */
	public function removeExpiredSignups($max_days = 12)
	{
		$success = false;
		if (is_int($max_days))
		{
			$sql = "update sc_signup set signup_date = null where signup_date + interval '$max_days days' < now() and enrolment_id is null";
			$sql2 = "delete from sc_signup where recommended is null and signup_date is null and enrolment_id is null";
			$transactions = array();
			$this->transaction('begin');
			$transactions[] = $this->update($sql);
			$transactions[] = $this->update($sql2);
			$success = $this->assessTransaction($transactions);
		}
		return $sucess;
	}

	/**
	 * getCompletionByModuleAndStudent
	 *
	 * has this module been completed by this student
	 * @return false if not completed
	 * otherwise the unix epoch time of when it was completed
	 */
	public function getCompletionByModuleAndStudent($course_code, $module, $student_id)
	{
		$completed = false;
		$sc_signup = $this->pluck('sc_signup', null, array('course_code'=>$course_code, 'student_id'=>$student_id));
		$submitted = $this->pluck('sc_submission', null, array('module_id' => $module, 'enrolment_id' => $sc_signup['enrolment_id']), 'timestamp');
		if ($submitted !== false)
		{
			$completed = strtotime($submitted['timestamp']);
		}
		return $completed;
	}

	/**
	 * addSubmission
	 * similar to QuestionSetDao::addResponse; updates the sc_submission table and
	 * calls addResponseQuestion to add the responses to that table.
	 * @param int $enrolment_id
	 * @param,int $module_id
	 * @param int $qset_id
	 * @param array(mixed) $response_array of question_name (from db)=>response
	 */
	public function addSubmission($enrolment_id, $module_id, $qset_id, $response_array)
	{
		$added = false;
		if (is_numeric($enrolment_id) and is_numeric($module_id) and is_numeric($qset_id))
		{
			$timestamp = date("M d o H:i:s");
			$random = mt_rand();
			$transaction_log = array();
			$insert_var_array = array('enrolment_id' => $enrolment_id, 'timestamp' => $timestamp, 'random_num' => $random, 'module_id' => $module_id, 'qset_id' => $qset_id);
			$this->transaction('begin');
			$transaction_log[] = $this->insertTableItem('sc_submission', $insert_var_array);
			if ($this->assessTransaction($transaction_log))
			{
				$current_response = array_pop((array_values($this->pluck('sc_submission', null, $insert_var_array, 'responseid'))));
				$responses = $this->addResponseQuestion($current_response, $response_array);
				if ($responses !== false)
				{
					$added = true;
				}
			}
		}
		return $added;
	}

	/**
	 * getEnrolmentDate
	 * A small helper function which gets the start date of an enrolment
	 * returns an ISO 8601 compatible string for use in other database queries
	 * Skips the time stamp.
	 * @param int $enrolment_id, id no. of the enrolment to check
	 * @return string in the format 1999-01-08 for 8th January 1999
	 * NOTE this function could be adapted to return other enrolment dates, with an additional parameter
	 */
	public function getEnrolmentDate($enrolment_id)
	{
		$earliest = $this->pluck('sc_enrolment', $enrolment_id, null, $column = 'start_date');
		$date_string = $earliest['start_date'];
		$start_date = array_shift(explode(' ', $earliest['start_date']));
		return $start_date;
	}

	/**
	 * checkSubmissionActivity
	 * checks if an enrolment has submitted any activities between two dates
	 * (default start date and one week later).
	 * @param int $enrolment_id to check
	 * @param string $earliest, the start date to check for activity, defaults to start date of enrolment
	 * @param string $latest, the end_date for the check, defaults to a week after the start date
	 * Input format for each of above otherwise ISO 8601: eg. 1999-01-08 for 8th January 1999 , though
	 * '08-Jan-1999' is also unambiguous. No spaces allowed
	 * @return int no. of submissions in that period from that enrolment
	 */ 
	public function checkSubmissionActivity($enrolment_id, $earliest = 'default', $latest = 'default')
	{
		$activity = 0;
		if ($earliest == "default")
		{
			$start_date = $this->getEnrolmentDate($enrolment_id);
		}
		else
		{
			$start_date = $earliest;
		}
		
		if ($latest == "default")
		{
			$latest = "date '$start_date' + interval '1 week 23 hours 59 minutes 59 seconds'";
		}
		else
		{
			$latest = "'$latest'";
		}
		$sc_sub = 'sc_submission';
		$sql = "select module_id from sc_submission WHERE " . $this->makeConstraintSQL(array('enrolment_id' => $enrolment_id), $sc_sub) . " AND " . $this->makeConstraintSQL(array('timestamp' => $start_date), $sc_sub, 'greater_than') . " AND timestamp < $latest";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$module_ids = array();
		while ($submission_row = $result->getRow())
		{
			$module_ids[] = $submission_row[0];
		}
		$activity = count($module_ids);
		return $activity;
	}

	/**
	 * removeSubmission
	 * removes a submission from the database
	 * @param int $enrolment_id
	 * @param,int $module_id. if left blank or a non-numeric value, will delete all of the submissions
	 * for a particular enrolment
	 * @return true if operation succeeded
	 */
	public function removeSubmission($enrolment_id, $module_id)
	{
		$success = false;
		$transaction_log = array();
		$constraint = array('enrolment_id' => $enrolment_id);
		if (is_numeric($module_id))
		{
			$constraint['module_id'] = $module_id;
		}
		if (is_numeric($enrolment_id))
		{
			$responseids = $this->suck('sc_submission', 'responseid', $constraint);
			$this->transaction('begin');
			if (count($responseids) > 0)
			{
				$transaction_log[] = $this->deleteTableItem('response_question', null, array('responseid' => $responseids));
				$transaction_log[] = $this->deleteTableItem('sc_submission', null, $constraint);
				if ($this->assessTransaction($transaction_log))
				{
					$success = true;
				}
			}
			else
			{
				$success = true;
			}
		}
		return $success;
	}

	/**
	 * addResponseQuestion
	 * a function which takes the response_id and populates the response_question table
	 * @todo This function could be called by QuestionSetDao::addResponse 
	 * if that function were to be refactored. One problem with that approach is that
	 * ShortCourseDao and QuestionSetDao are siblings, and this function is not quite
	 * appropriate for abstraction up to the MEIDSQLServerDao parent of both.
	 * This function would also need to be public
	 * @param int $response_id
	 * @param array(mixed) $response_array of question_name (from db)=>response
	 * (usually sent from addSubmission above)
	 * @return 1 if empty response_array, otherwise boolean results of database action
	 * TRUE if responses were added and FALSE if they weren't
	 */
	protected function addResponseQuestion($response_id, $response_array)
	{
		$added = null;
		//if all responses are empty, don't add them to the db.
		$current_response = (int)$response_id;
		$responses = array_values($response_array);
		foreach ($responses as $r)
		{
			if (empty($r))
			{
				array_shift($responses);
			}
		}
		if (!empty($responses))
		{
			$transaction_log = array();
			$this->transaction('begin');
			foreach ($response_array as $qname=>$response)
			{
				$q_id = getIDFromName($qname);
				if (empty($response))
				{
					continue;
				}
				else if (is_array($response))
				{	
					$multi_id = 1;
					foreach ($response as $multi)
					{
						$var_array = array("responseid"=>$current_response, "q_id"=>$q_id, "response"=>$multi, "multi_id"=>$multi_id);
						$result = $this->insertTableItem("response_question",$var_array);
						$transaction_log[] = $result;
						$multi_id ++;	
					}
				}
				else
				{
					$multi_id = 1;
					$var_array = array("responseid"=>$current_response, "q_id"=>$q_id, "response"=>$response, "multi_id"=>$multi_id);
					$result = $this->insertTableItem("response_question",$var_array);
					$transaction_log[] = $result;
				}
			}
			if ($this->assessTransaction($transaction_log) == false)
			{
				$added = false;
			}
			else
			{
				$added = true;
			}
		}
		else
		{
			// empty response set
			$added = 1;
		}
		return $added;
	}

	/**
	 * reallocateEnrolment
	 *
	 * @param int $enrolment_id
	 * @param string $tutor_id
	 * @return bool on success/failure
	 * @todo forward already submitted moderated activities
	 */
	public function reallocateEnrolment($enrolment_id, $tutor_id)
	{
		$transaction_log = array();
		$this->transaction('begin');
		$result = $this->updateTableItem("sc_enrolment", $enrolment_id, array('tutor_id' => $tutor_id));
		$transaction_log[] = $result;
		if ($this->assessTransaction($transaction_log) == false)
		{
			return false;
		}
		else
		{
			$mailbag = array(new ShortCourseEmailDispatcher('reallocation-tutor', $enrolment_id));
			// send this email straight away so that it appears first (we hope)
			mailbag($mailbag);
			// get a list of submissions for this enrolment
			$submission_list = $this->suck('sc_submission', array('module_id', 'responseid'), array('enrolment_id' => $enrolment_id), array('timestamp'));
			// if there are no submissions make an empty list
			if (!$submission_list)
			{
				$submission_list = array();
			}
			// narrow the list to include only the moderated ones
			foreach ($submission_list as $submission_data)
			{
				// generate an email to the new tutor with the text of the submission, including a warning that the previous tutor may have answered the submission
				$module_details = $this->getModuleDetails($submission_data['module_id']);
				if (shouldBeBoolean($module_details['moderated']))
				{
					$course_code = $this->pluck('sc_course_module', null, array('module_id' => $submission_data['module_id']));
					$submission_writer = new SubmissionWriter($course_code['course_code'], $module_details['module_id'], $module_details['question_sets'][0]);
					$submission_writer->sendMailToTutor('Note that this submission may have been answered by the previous tutor', $submission_data['responseid']);
					// add the output of that to the end of the warning message
					// $mailbag[] = new ShortCourseEmailDispatcher('submission-tutor', $enrolment_id, 'Note that this submission may have been answered by the previous tutor');
				}
			}
			return true;
		}
	}
}
?>

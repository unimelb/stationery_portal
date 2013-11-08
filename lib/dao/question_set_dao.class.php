<?php
/**
 * QuestionSet DAO
 * 
 * @package dao
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Required files
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/question_set.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/question_dao.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/meid_sqlserver_dao.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/get_id_from_name.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/should_be_boolean.inc.php");
/**
 * QuestionSet Dao (Database Access Object)
 * based on daophp5
 * allows creation, retrieval, updating and deletion of QuestionSets and Polls in the database
 * 
 * @package dao
 */
class QuestionSetDao extends MEIDSQLServerDao
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
	 * retrieves the details of a particular QuestionSet
	 * @param int $qset_id the id of the QuestionSet
	 * needs to access QuestionDao and AnswerSetDao objects also.
	 */
	 public function getQuestionSetByID($qset_id)
	 {
	 	// validate id
		if(!is_int($qset_id) && $qset_id <= 0) 
		{
			throw new InvalidArgumentException($qset_id." is not a valid argument");
		}
		$question_dao = new QuestionDao($this->dbconnect_string);
		// query db
		$sql = "SELECT * FROM questionset WHERE qset_id ='".$this->da->escape($qset_id)."'";
		$sql2 = "select q.q_id, c.name as category from qset_question q, category c where qset_id = '".$this->da->escape($qset_id)."' and c.category_id = q.category_id order by list_order, q_id";
		// note DISTINCT does not work with TEXT type in MS-SQL
		
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		// loop through results, create full QuestionSet object
		if($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			// just add water to create object
			$id = $row->offsetGet("qset_id");
			$feedback_string = $row->offsetGet("feedback");
			$intro_string = $row->offsetGet("introduction");
			
			$question_list = array();
			$categorised_questions = array();
			$answer_rules = array();
			// get categories used
			$categories = $this->getCategories($qset_id);
			$q_categories = array();
			$result2 = $this->retrieve($sql2);
			$result2->setFormat("ASSOC");
			if($result2->getNumRows() >= 1)
			{
				foreach($result2 as $row)
				{
					$q_id = $row->offsetGet("q_id");
					$category = $row->offsetGet("category");
					$q_categories[] = $category;
					$categorised_questions[] = $q_id;
				}
			}
			$question_set_q_ids = $this->getQIds($qset_id);
			foreach ($question_set_q_ids as $id_num)
			{
				$question_list[] = $question_dao->getQuestionByID($id_num);
			}
			//similarly minimal info for answer sets
			/*
			$answer_sets = $this->getAsetsByQuestion($qset_id);
			foreach($answer_sets as $as_id)
			{
				$rule = new AnswerSet(array(),array(),array(),array());
				$rule->setID($as_id);
				$answer_rules[] = $rule;
			}
			*/
			$answer_rules = $this->getAnswerRules($qset_id);
			$generic_feedback = new Feedback(GENERIC_SET, $feedback_string);
			$question_set = new QuestionSet($question_list, $answer_rules, array($generic_feedback),  $intro_string);
			$existing_hints = $this->suck("hint_qset", "keyword", array("qset_id" => $qset_id));
			$question_set->setStylist(new Stylist($existing_hints));
			foreach ($categories as $cat)
			{
				$question_set->addCategory($cat->getName(), $cat->getDescription(), $cat->getContext());
			}
			$cat_question_list = array();
			foreach ($categorised_questions as $id_num)
			{
				$cat_question_list[] = $question_dao->getQuestionByID($id_num);
			}
			foreach ($cat_question_list as $q)
			{
				$question_set->categoriseQuestion(array_shift($q_categories), $q);
			}
			$question_set->setID($id);
			return $question_set;
		}
		else
		{
			return false;
		}
		
	 }
	/**
	 * getQIds
	 * retrieves an integer list which corresponds to q_id
	 * @param int $qset_id the id of the question_set
	 * @return a list of integers corresponding to q_id
	 */
	public function getQIds($qset_id)
	{
		// validate id
		if(!is_int($qset_id) && $qset_id <= 0) 
		{
			throw new InvalidArgumentException($qset_id." is not a valid argument");
		}
		$ids = array();
		$sql3 = "SELECT question.q_id
		FROM question, qset_question
		WHERE question.q_id = qset_question.q_id
		AND qset_question.qset_id ='".$this->da->escape($qset_id)."'
		ORDER BY qset_question.list_order, question.q_id ASC";
		$result3 = $this->retrieve($sql3);
		$result3->setFormat("ASSOC");
		if ($result3->getNumRows() >= 1)
		{
			foreach ($result3 as $row)
			{
				$ids[] = (int)$row->offsetGet("q_id");
			}
		}
		return $ids;
	}
	/**
	 * getQuestionSetList
	 * retrieves a summary list of all QuestionSets in db
	 */
	public function getQuestionSetList()
	{
		// query database
		$sql = 'SELECT * FROM questionset ORDER by qset_id';
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$question_sets = new ArrayObject();
		// retrieve row and create user object
		if($result->getNumRows() >= 1) 
		{
			foreach($result as $row) 
			{
				$id = $row->offsetGet("qset_id");
				$feedback_string = $row->offsetGet("feedback");
				$intro_string = $row->offsetGet("introduction");
				// don't get the full details; this is just a summary
				$question_list = array();
				
				$answer_rules = array();
				$question_set = new QuestionSet($question_list, $answer_rules, $feedback_string,  $intro_string);
				$question_set->setID($id);
				$question_sets->append($question_set);
			}
			return $question_sets;
		}
		else
		{
			return false;
		}
		
	}
	/**
	 * gets a list of categories for a question set
	 * @param int $question_set_id, the id of the question set
	 * @return array of name=>description for each category used in the set
	 */
	public function getCategories($question_set_id)
	{
		$sql_categories = "SELECT DISTINCT q.category_id, c.name, cast(c.description as varchar(1000)) as description, cast(c.context as varchar(2000)) as context FROM category c, qset_question q WHERE q.qset_id = " . $this->da->escapeContext($question_set_id) . " AND c.category_id = q.category_id order by q.category_id";
		$result_categories = $this->retrieve($sql_categories);
		$categories = array();
		$result_categories->setFormat("ASSOC");
		if($result_categories->getNumRows() >= 1)
		{
			foreach($result_categories as $row)
			{
				$name = $row->offsetGet("name");
				$description = $row->offsetGet("description");
				$context = $row->offsetGet("context");
				$categories[] = new Category($name, $description, $context);
			}
		}
		return $categories;
	}
	/**
	 * returns true if a category has associated questions, false otherwise
	 * @param int $question_set_id, the id of the question set
	 * @param int $category_id, the id of the category
	 * @return bool $has_questions
	 */
	public function categoryHasQuestions($question_set_id, $category_id)
	{
		$has_questions = false;
		$sql = "select q_id from qset_question where category_id = " . $this->da->escapeContext($category_id) . " and qset_id = " . $this->da->escapeContext($question_set_id);
		$result = $this->retrieve($sql);
		if ($result->getNumRows() >= 1)
		{
			$has_questions = true;
		}
		return $has_questions;
	}
	/**
	 * gets a list of categories for a question set
	 * @param int $question_set_id, the id of the question set
	 * @return array of integers representing category_id for each category used in the set
	 */
	public function getCategoryIds($question_set_id)
	{
		$sql = "SELECT DISTINCT q.category_id FROM category c, qset_question q WHERE q.qset_id = " . $this->da->escapeContext($question_set_id) . " AND c.category_id = q.category_id UNION (SELECT category_id FROM qset_category WHERE qset_id = " . $this->da->escapeContext($question_set_id) . ")";
		// subqueries not allowed to be ordered by mssql
		// . " ORDER BY list_order, category_id)";
		$result_categories = $this->retrieve($sql);
		$category_ids = array();
		$result_categories->setFormat("ASSOC");
		if($result_categories->getNumRows() >= 1)
		{
			foreach($result_categories as $row)
			{
				$category_ids[] = $row->offsetGet("category_id");
			}
		}
		return $category_ids;
	}
	/**
	 * @param int $question_set_id the id of the question set in the db
	 * @return array of integers, (answerset_id)
	 * @TODO DEPRECATED (superceded by answer rules
	 */
	public function getAsetsByQuestion($question_set_id)
	{
		// validate id
		if($question_set_id <=0) 
		{
			throw new InvalidArgumentException($question_set_id." is not a valid argument");
		}
		$sql = "SELECT answerset_id FROM qset_aset WHERE qset_id = '".$this->da->escape($question_set_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$answerset_list = array();
		if($result->getNumRows())
		{
			foreach($result as $row)
			{
				$answerset_list[] = $row->offsetGet(0);
			}
		}
		return $answerset_list;	
	}
	/**
	 * getAnswerRules
	 * retrieves the rules for a particular question_set
	 * @param int $question_set_id the id of the question set in the db
	 * @return array of AnswerRules
	 */
	public function getAnswerRules($question_set_id)
	{
		// validate id
		if($question_set_id <=0) 
		{
			throw new InvalidArgumentException($question_set_id." is not a valid argument");
		}
		$qset_rules = array();
		$rules = array();
		$qset_rules = $this->suck("answer_rule", "rule_id", array("qset_id" => $question_set_id));
		foreach ($qset_rules as $rule_id)
		{
			$rules[] = $this->getRuleById($rule_id);
		}
		return $rules;
	}
	/**
	 * getRuleById
	 * used by getAnswerRules
	 * @param int $rule_id
	 * @return AnswerRule
	 */
	public function getRuleById($rule_id)
	{
		$phrases = array();
		$rule = null;
		$phrase_ids = array();
		$phrase_ids = $this->suck("answer_phrase", "phrase_id", array("rule_id" => $rule_id));
		foreach ($phrase_ids as $phrase_id)
		{
			$phrases[] = $this->getPhraseById($phrase_id);
		}
		$rule_feedback = $this->pluck("answer_rule", null, array("rule_id" => $rule_id), "feedback");
		$rule_feedback_text = $rule_feedback["feedback"];
		$rule = new AnswerRule($phrases, $rule_feedback_text);
		$rule->setID($rule_id);
		return $rule;
	}
	/**
	 * getPhraseById
	 * used by getRuleById
	 * @param int $phrase_id, corresponds to the database
	 * @return AnswerPhrase
	 */
	public function getPhraseById($phrase_id)
	{
		// note: views have no primary key
		$phrase_attributes = $this->pluck("phrase_view", null, array("phrase_id" => $phrase_id), '*');
		$phrase_extra = $this->pluck("answer_phrase", null, array("phrase_id" => $phrase_id), '*');
		//$phrase_answer_attributes = $this->pluck("answer_phrase", $phrase_id, null, '*');
		// need to add 'category' and 'answer_type' to $phrase_attributes, if they exist
		if (isset($phrase_extra["category_id"]))
		{
			$keyword = $this->pluck("category", $phrase_extra["category_id"], null, 'name');
			$phrase_attributes["category"] = $keyword['name'];
		}
		else
		{
			$phrase_attributes["category"] = "any";
		}
		$answers = $this->suck("phrase_answer_type", "answer", array("phrase_id" => $phrase_id));
		if (count($answers) == 1)
		{
			$phrase_attributes["answer_type"] = $answers[0];
		}
		else if (count($answers) > 1)
		{
			$phrase_attributes["answer_type"] = $answers;
		}
		else
		{
			$phrase_attributes["answer_type"] = "any";
		}
		$phrase_questions = array();
		if (isset($phrase_extra["question_count"]))
		{
			$questions = strval($phrase_extra["question_count"]);
		}
		$phrase_questions = $this->suck("phrase_question", "q_id", array("phrase_id" => $phrase_id));
		if (!empty($phrase_questions))
		{
			$questions = array();
			//assemble a list of Questions
			$question_dao = new QuestionDao($this->dbconnect_string);
			foreach ($phrase_questions as $q_id)
			{
				$questions[] = $question_dao->getQuestionByID($q_id);
			}
		}
		// since questions can't be empty, to avoid errors:
		if (empty($questions))
		{
			$questions = 0;
		}
		$my_phrase = new AnswerPhrase($phrase_attributes["qualifier"], $questions, $phrase_attributes["correctness"], $phrase_attributes["category"], $phrase_attributes["answer_type"]);
		$my_phrase->setID($phrase_id);
		return $my_phrase;
	}
	/**
	 * updates the QuestionSet
	 * @param int $qset_id id number of the QuestionSet
	 * @param array of strings $var_array a list of object variables: column=>value, with columns including:
	 *	introduction
	 *	feedback
	 */
	public function updateQuestionSet($qset_id, $var_array)
	{
		$result = $this->updateTableItem('questionset', $qset_id, $var_array);
		return $result;
	}

	/**
	 * create QuestionSet
	 *
	 * creates a QuestionSet in the database
	 * @param QuestionSet the QuestionSet to create
	 */
	public function createQuestionSet($question_set)
	{
		// validate id
		if(get_class($question_set) != "QuestionSet") 
		{
			throw new InvalidArgumentException($question_set." is not a valid argument");
		}
		$feedback = $question_set->getGenericFeedback()->getText();
		$introduction = $question_set->getIntro();
		$sql = "INSERT INTO questionset (feedback, introduction) values('".$this->da->escape($feedback)."',
		'".$this->da->escape($introduction)."')";
		$result = $this->update($sql);
		return $result;
	}
	
	/**
	 * Poll functions
	 * used to update and retrieve Poll Question sets
	 * (doesn't really deserve its own Dao, though I am prepared to change my mind)
	 */
	 /**
	  * addResponse
	  * adds a response to a poll
	  * @param int $poll_id
	  * @param array $response_array of question_name (from db)=>response
	  */
	 public function addResponse($id, $response_array, $table = 'response')
	 {
		// validate id 
		// poll should exist and be set to Active (=1)
	 	if ($table == 'sc_submission' or $this->pollIsActive($id))
	 	{
	 		//if all responses are empty, don't add them to the db.
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
				$foreign_key = null;
				switch ($table)
				{
					case 'sc_submission':
						$foreign_key = 'enrolment_id';
						break;
					default:
						$foreign_key = 'poll_id';
						break;
				}
				$timestamp = date("M d o H:i:s");
				$random = mt_rand();
				$insert_var_array = array($foreign_key => $id, 'timestamp' => $timestamp, 'random_num' => $random);
				$result = $this->insertTableItem($table, $insert_var_array);
				// then insert the questions into response_question
				//$current_response = $this->getLastID("response");
				$current_response = array_pop($this->pluck($table, null, array($foreign_key => $id, 'random_num' => $random), 'responseid'));
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
							$result2 = $this->insertTableItem("response_question",$var_array);
							$multi_id ++;	
						}
					}
					else
					{
						$multi_id = 1;
						$var_array = array("responseid"=>$current_response, "q_id"=>$q_id, "response"=>$response, "multi_id"=>$multi_id);
						$result2 = $this->insertTableItem("response_question",$var_array);
					}
				}
				return $result;
	 		}
	 		else
	 		{
	 			return 1;
	 		}
	 	}
	 	else
	 	{
	 		return false;
	 	}
	 }
	 /**
	  * pollIsActive
	  * determines whether a poll exists and has its Active bool set to 1
	  * If so, the function returns true, false otherwise
	  * @param int $poll_id
	  * @return bool
	  */
	 public function pollIsActive($poll_id)
	 {
	 	$sql = "SELECT isactive FROM poll WHERE poll_id = '".$this->da->escape($poll_id)."'";
	 	$result = $this->retrieve($sql);
	 	$result->setFormat("NUM");
	 	$activity = false;
	 	if($result->getNumRows() == 1)
	 	{
	 		$row = $result->getRow();
	 		$is_active = $row->offsetGet(0);
	 		if (shouldBeBoolean($is_active))
	 		{
	 			$activity = true;
	 		}
	 	}
	 	return $activity;
	 }
	/**
	 * getPolls
	 * @param string username (optional)
	 * @return a list of poll ids (integers) optionally based on a username
	 * to get the latest, array_pop the results, treat null result as no poll created
	 */
	public function getPolls($username="")
	{
		$sql = "SELECT p.poll_id from poll p";
		if (!empty($username))
		{
			$sql .= " where p.owner = '".$this->da->escape($username)."'";
		}
		$sql .= " order by p.creation_date";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$id_list = array();
		if($result->getNumRows())
		{
			foreach($result as $row)
			{
				$id_list[] = $row->offsetGet(0);
			}
		}
		return $id_list;	
	}
	/**
	 * createPoll
	 * @param string $username username
	 * @param int $qset_id question set id for poll
	 * @param string $description, optional description of the poll
	 */
	public function createPoll($username=null, $qset_id, $description = "")
	{
		$make_poll = false;
		if (is_numeric($qset_id))
		{
			$this->insertTableItem('poll', array(
					'qset_id' => $qset_id, 
					'description' => $description,
					'isactive' => true,
					'isapproved' => true,
					'owner' => $username,
					'creation_date' => date("c"))
				);
		}
		return $make_poll;
	}
	/**
	 * deletePoll
	 * @param int $poll_id
	 * @return boolean true if delete successful
	 * calls delete table item to remove a poll from the database
	 */
	public function deletePoll($poll_id)
	{
		$success = false;
		$transaction_array = array();
		if (is_numeric($poll_id))
		{
			$this->transaction('begin');
			$transaction_array[] = $this->deleteTableItem('poll', $poll_id);
			if ($this->assessTransaction($transaction_array))
			{
				$success = true;
			}
		}
		return $success;
	}
	/**
	 * updatePollDescription
	 * @param int poll_id
	 * @param string text new description
	 */
	public function updatePollDescription($poll_id, $description)
	{
		$updated_poll = false;
		if (is_numeric($poll_id))
		{
			$updated_poll = $this->updateTableItem('poll', null, array('description' => $description), array('poll_id' => $poll_id));
		}
		return $updated_poll;
	}
	/**
	 * getPollData
	 * retrieves Poll-related information
	 * @param int $poll_id the id to retrieve data from
	 * @param string the name of a column to retrieve data from (default *)
	 * @returns an associative array of column=>value, or FALSE if not found
	 */
	public function getPollData($poll_id, $column="*")
	{
		return $this->pluck('poll', null, array('poll_id' => $poll_id), $column);
	}
	/**
	 * getQuestionResponses
	 * retrieves all of the responses to a particular question in a poll
	 * and returns them as an array (to be used as a stack).
	 * @param int poll_id the id no. of the poll
	 * @param int q_id the id no. of the Question
	 * @return array strings $responses
	 */
	public function getQuestionResponses($poll_id, $q_id)
	{
		$sql = "SELECT q.response FROM response_question q, response r WHERE r.responseid = q.responseid AND q.q_id = '".$this->da->escape($q_id)."' AND r.poll_id = '".$this->da->escape($poll_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$responses = array();
		if($result->getNumRows())
		{
			foreach($result as $row)
			{
				$responses[] = $row->offsetGet(0);
			}
		}
		return $responses;	
	}
	/**
	 * getTotalResponses
	 * retrieves the total number of responses for a given Poll
	 * @param int poll_id
	 * @return int total no. of responses
	 */
	public function getTotalResponses($poll_id)
	{
		$total = 0;
		if (is_numeric($poll_id))
		{
			$sql = "select count(*) from response where poll_id = ".$this->da->escapeContext($poll_id);
			$result = $this->retrieve($sql);
			$result->setFormat("NUM");
			if($result->getNumRows() == 1)
			{
				$row = $result->getRow();
				$total = $row->offsetGet(0);
			}
		}
		return $total;
	}

	/**
	 * getIndividualResponse
	 * retrieves responses to all questions from one respondent
	 * @param int $response_id
	 * @param boolean $blank_responses
	 * If TRUE, returns a blank response ('') for any unanswered questions in the set
	 * (default FALSE)
	 * @return associative array of q_id => response
	 */
	public function getIndividualResponse($response_id, $blank_responses = false)
	{
		$q_ids = array();
		
		$sql = "SELECT * FROM response_question WHERE responseid = '".$this->da->escape($response_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$responses = array();
		if ($result->getNumRows())
		{
			foreach ($result as $row)
			{
				// need to put responses in an array if it's a checkbox question
				if (array_key_exists($row->offsetGet(1), $responses))
				{
					if (is_array($responses[$row->offsetGet(1)]))
					{
						$responses[$row->offsetGet(1)][] = $row->offsetGet(2);
					}
					else
					{
						$responses[$row->offsetGet(1)] = array($responses[$row->offsetGet(1)], $row->offsetGet(2));
					}
				}
				else
				{
					$responses[$row->offsetGet(1)] = $row->offsetGet(2);
				}
			}
		}
		if ($blank_responses == true)
		{
			$qset_id = $this->pluck('sc_submission', null, array('responseid' => $response_id), 'qset_id');
			$q_ids = $this->getQIds($qset_id['qset_id']);
			$response_stack = array();
			foreach ($q_ids as $q_id)
			{
				if (array_key_exists($q_id, $responses))
				{
					$response_stack[$q_id] = $responses[$q_id];
				}
				else
				{
					$response_stack[$q_id] = '';
				}
			}
			$responses = $response_stack;
		}
		return $responses;
	}

	/**
	 * getIndividualResponses
	 * retrieves responses to all questions from all respondents to one poll
	 * @param int $poll_id
	 * @param array of strings with sql filters (e.g., array('timestamp > 'Mar 28 2008'))
	 * @return associative array of q_id => response
	 */
	public function getIndividualResponses($poll_id, $constraints = array())
	{
		$sql = "SELECT rq.responseid, q.name, rq.response, rq.multi_id, r.timestamp FROM response_question rq, response r, question q WHERE r.poll_id = " . $this->da->escape($poll_id) . " and r.responseid = rq.responseid and rq.q_id = q.q_id";
		if (!empty($constraints))
		{
			foreach ($constraints as $constraint)
			{
				$sql .= " and $constraint";
			}
		}
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$responses = array();
		if ($result->getNumRows())
		{
			foreach($result as $row)
			{
				// need to put responses in an array if it's a checkbox question
				if (array_key_exists($row->offsetGet(0), $responses))
				{
					$responses[$row->offsetGet(0)]['responses'][] = array($row->offsetGet(1), $row->offsetGet(2), $row->offsetGet(3));
				}
				else
				{
					$responses[$row->offsetGet(0)] = array(
						'time' => $row->offsetGet(4),
						'responses' => array(array($row->offsetGet(1), $row->offsetGet(2), $row->offsetGet(3))));
				}
			}
		}
		return $responses;
	}

	/**
	 * retrieve the number of people completing the survey who answered this question
	 *
	 * @param int poll_id, corresponds to poll_id in database
	 * @param int q_id, corresponds to q_id in database
	 * @return int total
	 */
	public function getTotalAnswered($poll_id, $q_id)
	{
		$sql = "SELECT COUNT(*) FROM (SELECT DISTINCT r.* FROM response r, response_question rq WHERE r.responseid = rq.responseid AND r.poll_id = " . $this->da->escape($poll_id) . " AND rq.q_id = " . $this->da->escape($q_id) . ") AS s1";
		$result = $this->retrieve($sql);
		$total = 0;
		if ($result->getNumRows() == 1)
		{
			foreach($result as $row)
			{
				$total = $row->offsetGet(0);
			}
		}
		return $total;
	}
}
?>

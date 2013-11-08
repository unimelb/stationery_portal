<?php
/**
 * Answer Set DAO
 * 
 * @package dao
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/daophp5/DAO.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/answer_set.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/feedback.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/question_dao.class.php");

/**
 * AnswerSetDao (Database Access Object)
 * based on daophp5
 * allows creation, retrieval, updating and deletion of AnswerSets in the database
 *
 * @package dao
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 * @todo clean up input for updateAnswerSet so we can dump the preg_match
 */
class AnswerSetDao extends DAO
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
	 * retrieves the details of a particular AnswerSet
	 * @param int $as_id the id of the AnswerSet
	 * @return AnswerSet, if it exists
	 */
	public function getAnswerSetByID($as_id)
	{
		// validate id
		if(!is_int($as_id) && $as_id <= 0) 
		{
			throw new InvalidArgumentException($q_id." is not a valid argument");
		}
		// query db
		$sql = "SELECT * FROM answerset WHERE answerset_ID ='".$this->da->escape($as_id)."'";
		$sql2 = "SELECT question.q_id, question.prompt, aset_question.correctness
		FROM question, aset_question 
		WHERE question.q_id = aset_question.q_id 
		AND answerset_ID ='".$this->da->escape($as_id)."'";
		$result = $this->retrieve($sql);
		$result2 = $this->retrieve($sql2);
		$result->setFormat("ASSOC");
		$result2->setFormat("ASSOC");
		$correct_array = array();
		$incorrect_array = array();
		$unanswered_array = array();
		$answered_array = array();
		// loop through results, create full AnswerSet object
		// first, instantiate required Questions into the appropriate correctness category
		if($result2)
		{
			foreach($result2 as $row)
			{
				// don't get the full details; this is just a summary
				$name = 'Q'.$row->offsetGet("q_id");
				$prompt = $row->offsetGet("prompt");
				$correctness = strtolower($row->offsetGet("correctness"));
				$question = new Question($name,$prompt,null);
				switch($correctness)
				{
					case "correct":
						$correct_array[] = $question;
						break;
					case "incorrect":
						$incorrect_array[] = $question;
						break;
					case "unanswered":
						$unanswered_array[] = $question;
						break;
					case "answered":
						$answered_array[] = $question;
						break;
					default:
						// do nothing
				}
			}
			
		}
		if($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			// just add water to create object
			$id = $row->offsetGet("answerset_ID");
			$answer_feedback_text = $row->offsetGet("feedback");
			$answer_feedback = new Feedback("answer",$answer_feedback_text);
			$answer_set = new AnswerSet($correct_array, $incorrect_array, $unanswered_array, $answered_array, $answer_feedback);
			$answer_set->setID($id);
			return $answer_set;	
		}
		else
		{
			return false;
		}
	}
	
	/**
	 *@param int $answerset_id corresponds to answerset_ID
	 *@return string feedback string
	 */
	public function getFeedbackText($answerset_id)
	{
		$answer_feedback_text = "";
		$sql = "SELECT feedback FROM answerset WHERE answerset_ID ='".$this->da->escape($answerset_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		if($result->getNumRows() == 1)
		{
			$row = $result->getRow();
			$answer_feedback_text = $row->offsetGet("feedback");
		}
		return $answer_feedback_text;
	}
	
	/**
	 * creates a new AnswerSet
	 * @param AnswerSet
	 * @param int question_set_id
	 */
	 public function createAnswerSet($answer_set, $question_set_id)
	 {
	 	$sql = "INSERT INTO answerset (feedback) VALUES (
	 	'".$this->da->escape($answer_set->getFeedback()->getText())."'
	 	)";
	 	$result = $this->update($sql);
	 	$answerset_id = $this->getLastID("answerset");
	 	$question_array = $answer_set->getAnswerSet();
	 	foreach($question_array as $correctness=>$questions)
	 	{
	 		foreach($questions as $question)
	 		{
	 			$q_id = $question->getIDFromName();
	 			$this->createAsetQuestion($answerset_id, $q_id, $correctness);
	 		}
	 	}
	 	$this->createQsetAset($answerset_id, $question_set_id);
		return $result;
	 	
	 }
	/**
	 * updates a particular AnswerSet in the database
	 * @param int answerset_id, the database identity of the AnswerSet to be updated
	 * @param array of arrays of questions:
	 *	'correct'=>array of positive integers (q_id)
	 *	'incorrect'=>array of positive integers (q_id)
	 *	'unanswered'=>array of positive integers (q_id)
	 *	'answered'=>array of positive integers (q_id)
	 * @param Feedback the feedback of the AnswerSet
	 */
	public function updateAnswerSet($answerset_id, $question_array, $feedback)
	{
		// validate id
		if(!is_int($answerset_id) && $answerset_id <= 0) 
		{
			throw new InvalidArgumentException($q_id." is not a valid argument");
		}
		$sql = "UPDATE answerset 
		SET feedback ='".$this->da->escape($feedback->getText())."' WHERE answerset_ID = '".$this->da->escape($answerset_id)."'";
		$result = $this->update($sql);
		// update aset_question table
		$question_list_int = array();
 		$former_question_list_int = array();
 		
 		//array("correct"=>array(),"incorrect"=>array(),"unanswered"=>array(),"answered"=>array());
		$former_question_list = $this->getAnswerSetByID($answerset_id)->getAnswerSet();
		foreach($former_question_list as $correctness=>$question_list)
		{
			foreach($question_list as $question)
			{
				//print $question->getIDFromName();
				$former_question_list_int[] = $question->getIDFromName();
			}
		}
		foreach($question_array as $correctness=>$question_list)
		{
			// need to createAsetQuestion if it didn't already exist,
			// or delete it if it did and is no longer there
			// if A is $question_list and B is the $former_question_list
			// then 
			// if q_id is in A then create,
			// if q_id is in A & B then update
			// if q_id is not in A then delete 
			//$former_question_list_int = $this->getAsetQuestionsByCorrectness($answerset_id, $correctness);
			
			foreach($question_list as $q_id)
			{
				preg_match('/Q([\d]+)$/', $q_id, $matches); // dump the 'Q'
				$q_id = $matches[1];
				$question_list_int[] = $q_id;
				$in_former_list = array_search($q_id, $former_question_list_int);
				if($in_former_list === false)
				{
					$this->createAsetQuestion($answerset_id,$q_id,$correctness);
				}
				else
				{
					$this->updateAsetQuestion($answerset_id,$q_id,$correctness);
				}
			}
			
		}
		foreach(array_diff($former_question_list_int, array_intersect($question_list_int, $former_question_list_int)) as $q_id)
		{
			$this->deleteAsetQuestion($answerset_id,$q_id);
		}
		
		return $result;
	}
	/**
	 * @param int answerset_id: the id number of the answerset
	 * @param int q_id: the id number of the Question
	 * @param string correctness: one of 'correct', 'incorrect', 'unanswered' or 'answered'
	 */
	public function updateAsetQuestion($answerset_id, $q_id, $correctness)
	{
		$sql = "UPDATE aset_question SET correctness = '".$this->da->escape($correctness)."' WHERE answerset_ID = '".$this->da->escape($answerset_id)."' AND q_id = '".$this->da->escape($q_id)."'";
		$result = $this->update($sql);
		return $result;
	}
	
	/**
	 * @param int answerset_id: the id number of the answerset
	 * @param string correctness: one of 'correct', 'incorrect', 'unanswered' or 'answered'
	 * @return a list of integers ($q_id)
	 */
	public function getAsetQuestionsByCorrectness($answerset_id, $correctness)
	{
		$sql = "SELECT q_id FROM aset_question WHERE correctness = '".$this->da->escape($correctness)."' AND answerset_ID = '".$this->da->escape($answerset_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$question_list = array();
		if($result->getNumRows())
		{
			$row = $result->getRow();
			foreach($row as $q_id)
			{
				$question_list[] = $q_id;
			}
		}
		return $question_list;
	}
	
	/**
	 * @return string a correctness string ('correct','incorrect','unanswered','answered'
	 * used to populate the content stack
	 * @param int answerset_id: the id number of the answerset
	 * @param int q_id: the id number of the Question
	 */
	public function getAsetCorrectnessByQuestion($answerset_id, $q_id)
	{
	 	$sql = "SELECT correctness FROM aset_question WHERE q_id = '".$this->da->escape($q_id)."' AND answerset_ID = '".$this->da->escape($answerset_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		if($result->getNumRows() == 1)
		{
			$row = $result->getRow();
			$correctness = $row->offsetGet(0);
		}
		else
		{
			$correctness = null;
		}
		return $correctness;
	}
	
	/**
	 * @param int answerset_id: the id number of the answerset
	 * @param int q_id: the id number of the Question
	 */
	public function deleteAsetQuestion($answerset_id,$q_id)
	{
		$sql = "DELETE FROM aset_question WHERE q_id = '".$this->da->escape($q_id)."' AND answerset_ID = '".$this->da->escape($answerset_id)."'";
		$result = $this->update($sql);
		return $result;
	}
	/**
	 * @param int answerset_id: the id number of the answerset
	 * @param int q_id: the id number of the Question
	 * @param string correctness: one of 'correct', 'incorrect', 'unanswered' or 'answered'
	 */
	public function createAsetQuestion($answerset_id, $q_id, $correctness)
	{
		$sql = "INSERT INTO aset_question VALUES(
		'".$this->da->escape($answerset_id)."',
		'".$this->da->escape($q_id)."',
		'".$this->da->escape($correctness)."')";
		$result = $this->update($sql);
		return $result;
	}
	
	/**
	 * @param int answerset_id: the id number of the answerset
	 * @param int question_set_id: the id number of the Question Set
	 */
	public function createQsetAset($answerset_id, $question_set_id)
	{
		// validate id
		if($answerset_id <=0 or $question_set_id <=0) 
		{
			throw new InvalidArgumentException($answerset_id." with ".$question_set_id." is not a valid argument");
		}
		$sql = "INSERT INTO qset_aset VALUES(
		'".$this->da->escape($question_set_id)."',
		'".$this->da->escape($answerset_id)."'
		)";
		$result = $this->update($sql);
		return $result;
	}
	
	/**
	 * getLastID
	 * Gets the latest id of a question or other table with an identity
	 * @param string table name of table having an IDENTITY column
	 * @return int id of latest IDENTITY value
	 */
	public function getLastID($table)
	{
// 	 $row = null;
// 	 require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/get_last_id.inc.php");
		$sql = "SELECT IDENT_CURRENT('".$this->da->escape($table)."')";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$row = $result->getRow();
		return $row[0];
	}
}
?>

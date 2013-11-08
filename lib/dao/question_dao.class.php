<?php
/**
 * Question DAO
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
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/meid_sqlserver_dao.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/question/question.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/question/checkbox_question.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/feedback.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/should_be_boolean.inc.php");

/**
 * QuestionDao (Database Access Object)
 * based on daophp5
 * allows creation, retrieval, updating and deletion of Questions in the database
 * 
 * @package dao
 */
class QuestionDao extends MEIDSQLServerDao
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
	 * retrieves the details of a particular Question
	 * @param int $q_id the id of the Question
	 */
	public function getQuestionByID($q_id)
	{
	 	// validate id
		if(!is_int($q_id) && $q_id <= 0) 
		{
			throw new InvalidArgumentException($q_id." is not a valid argument");
		}
		$existing_hints = null;
		// query db
		$sql = "SELECT * FROM question WHERE q_id =".$this->da->escape($q_id);
		$sql2 = "SELECT * FROM radio WHERE q_id =".$this->da->escape($q_id)." ORDER BY radio_id";
		$result = $this->retrieve($sql);
		$result2 = $this->retrieve($sql2);
		$result->setFormat("ASSOC");
		$result2->setFormat("ASSOC");
		// loop through results, create full Question object
		if($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			// just add water to create object
			$id = $row->offsetGet("q_id");
			$prompt_string = $row->offsetGet("prompt");
			
			//$answer_string = $row->offsetGet("answer");
			$answer_array = $this->retrieveAnswer($id);
			$answer_final = null;
			if (!empty($answer_array))
			{
				$answer_final = $answer_array[0];
			}
			// default name is id no. now- need to do some string work to fix
			$name_string = "_Q" . $id;
			if ($row->offsetGet("name") and $row->offsetGet("name") != " ")
			{
				$name_string = $row->offsetGet("name");
			}
			$correct_string = $row->offsetGet("correct");
			$incorrect_string = $row->offsetGet("incorrect");
			$answered_string = $row->offsetGet("answered");
			$unanswered_string = $row->offsetGet("unanswered");
			$context_string = $row->offsetGet("context");
			$imponderable_flag = $row->offsetGet("imponderable");
			$trailing_text = $row->offsetGet("trailing_text");
			$general_feedback = $row->offsetGet("general_feedback");
			$short_answer_flag = $row->offsetGet("short_answer");
			$multiple_flag = $row->offsetGet("multiple");
			$response_summary = $row->offsetGet("response_summary");
			$imponderable = shouldBeBoolean($imponderable_flag);
			$feedback_correct = new Feedback("correct", $correct_string);
			$feedback_incorrect = new Feedback("incorrect", $incorrect_string);
			$feedback_unanswered = new Feedback("unanswered", $unanswered_string);
			$feedback_answered = new Feedback("answered", $answered_string);
			$feedback_array = array($feedback_correct, $feedback_incorrect, $feedback_unanswered, $feedback_answered);
			// if Radio Options are present then this is a RadioOptionQuestion or a CheckboxQuestion
			$option_list = array();
			$option_feedback = array();
			//$option_answer_type = array();
			if($result2->getNumRows() >= 1)
			{
				foreach($result2 as $row)
				{
					$radio_id = $row->offsetGet("radio_id");
					$option_description = $row->offsetGet("description");
					$option_feedback_string = $row->offsetGet("feedback");
					//$answer_type_string = $row->offsetGet("answer_type");
					$option_name = $name_string.'_'.$radio_id;
					$option_feedback[$option_name] = new Feedback("option", $option_feedback_string);
					$option_list[$option_name] = $option_description;
				}
			}
			$existing_hints = $this->suck("hint_question", "keyword", array("q_id" => $q_id));
			if (in_array("multiple",$existing_hints) and !empty($option_list))
			{
				$option_set_list = array();
				$option_set_list = $this->retrieveOptionSets($q_id);
				$answer_option_set = new OptionSet($answer_array, null);
				$question = new CheckboxQuestion($name_string, $prompt_string, $answer_option_set, $feedback_array, $context_string, $imponderable, $option_list, $option_set_list);
				/*
				foreach ($option_set_list as $opt_set)
				{
					$description = $question->getAnswerText($opt_set->getNames());
					$question->setAnswerType($description, $description);
				}
				*/
			}
			else if($option_list)
			{
				$question = new RadioOptionQuestion($name_string, $prompt_string, $answer_final,$feedback_array, $context_string, $imponderable, $option_list, $option_feedback);
				/*
				foreach ($option_list as $name => $description)
				{
					$question->setAnswerType($description, $description);
				}
				*/
			}
			else
			{
				$question = new Question($name_string, $prompt_string, $answer_final,$feedback_array, $context_string, $imponderable);
				if(shouldBeBoolean($short_answer_flag))
				{
					$question->setShortAnswer();
				}
			}
			$answer_types = $this->suck('type_question', array('answer_text', 'answer_type'), array("q_id" => $q_id));
			foreach ($answer_types as $atype)
			{
				if (isset($atype['answer_type']) and isset($atype['answer_text']))
				{
					//print"<pre>atype: {$atype['answer_type']}</pre>";//test
					//print_r($question->getAllAnswerTypes());//test
					$question->setAnswerType($atype['answer_type'], $atype['answer_text']);
					//print_r($question->getAllAnswerTypes());//test
				}
			}
			
			//post-construction setters
			$question->addStyleHint($existing_hints);
			$question->setTrailingText($trailing_text);
			$question->setGeneralFeedback($general_feedback);
			$question->setSummary($response_summary);
			return $question;
		}
		else
		{
			return false;
		}
	}
	/**
	 * retrieveAnswer
	 * retrieves entries from the answer table for a given q_id
	 * @param int q_id, the identity of the Question which owns this answer
	 * @return array of strings
	 */
	public function retrieveAnswer($q_id)
	{
		$answer = array();
		$sql = "SELECT answer FROM answer WHERE q_id =" . $this->da->escape($q_id);
		//$sql .= " ORDER BY multi_id";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		if($result->getNumRows() >= 1) 
		{
			foreach ($result as $row)
			{
				$answer[] = $row->offsetGet(0);
			}
		}
		return $answer;
	}
	/**
	 * retrieves OptionSet info from the database and returns a list of Option Sets
	 * @param int $q_id the question retrieve option sets for
	 * @return array(OptionSet) $option_set_list
	 */
	public function retrieveOptionSets($q_id)
	{
		$option_set_list = array();
		$option_set_array = $this->suck('option_set', 'option_set_id', array('q_id' => $q_id));
		foreach($option_set_array as $opt_set_id)
		{
			$option_set_list[] = $this->getOptionSetById($opt_set_id, $q_id);
		}
		return $option_set_list;
	}
	/**
	 * getOptionSet by id
	 * @param int $option_set_id, the id number of the OptionSet
	 * @param int $q_id, the id number of the question
	 * @return OptionSet
	 */
	public function getOptionSetById($option_set_id, $q_id)
	{
		$name_array = $this->pluck("question", $q_id, null, 'name');
		$name_string = $name_array['name'];
		$true_false_moot = array('1' => 'true', '2' => 'false', '3' => 'moot');
		$option_set = null;
		$all_radios = $this->suck('radio', array('radio_id', 'description'), array("q_id" => $q_id));
		$feedback_array = $this->pluck('option_set', $option_set_id, null, 'feedback');
		$feedback = $feedback_array['feedback'];
		//$all_option_names = $this->makeOptionName($name_string, $all_radios['radio_id']);
		$all_names = array();
		$all_desc = array();
		foreach($all_radios as $radio_array)
		{
			if (!empty($radio_array['radio_id']))
			{
				$all_names[] = $radio_array['radio_id'];
			}
			if (!empty($radio_array['description']))
			{
				$all_desc[] = $radio_array['description'];
			}
		}
		$all_names = $this->makeOptionName($name_string, $all_names);
		$all_option_names = array_combine($all_names, $all_desc);
		$radios = $this->suck("option_set_option", "radio_id", array("option_set_id" => $option_set_id));
		$option_names = $this->makeOptionName($name_string, $radios);
		$rules = $this->suck("option_set_rule", '*', array("option_set_id" => $option_set_id));
		$option_set = new OptionSet($option_names, $feedback, $all_option_names);
		foreach ($rules as $rule_array)
		{
			$option_set_rule_id = $rule_array['option_set_rule_id'];
			$opt_set_rule = $this->getOptionSetRuleById($option_set_id, $option_set_rule_id);
			$option_set->defineOptionRule($opt_set_rule['min'], $opt_set_rule['option_names'], $opt_set_rule['condition'], $opt_set_rule['absoluteness'], $opt_set_rule['max']);
		}
		$option_set->setID($option_set_id);
		return $option_set;
	}
	/**
	 * @param int $option_set_rule_id
	 * @return array(min => int, $option_names=>array(strings), condition => string, absoluteness => bool, max => int)
	 */
	public function getOptionSetRuleById($option_set_id, $option_set_rule_id)
	{
		$output = array();
		$sql = "select q.name from option_set o, question q where q.q_id = o.q_id and o.option_set_id = " . $this->da->escape($option_set_id);
		$result = $this->update($sql);
		$result->setFormat("ASSOC");
		if($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			$name_string = $row->offsetGet('name');
		}
		$radios = $this->suck("option_set_option", "radio_id", array("option_set_id" => $option_set_id, "option_set_rule_id" => $option_set_rule_id ));
		$option_names = $this->makeOptionName($name_string, $radios);
		$rule = $this->pluck("option_set_rule", '*', array("option_set_id" => $option_set_id, "option_set_rule_id" => $option_set_rule_id));
		$output['min'] = (int)$rule['minimum'];
		if (isset($rule['maximum']))
		{
			$output['max'] = (int)$rule['maximum'];
		}
		else
		{
			$output['max'] = count($radios);
		}
		$condition_array = $this->pluck('condition', 'keyword', array('condition_id' => $rule['condition_id']));
		$output['option_names'] = $option_names;
		$output['condition'] = $condition_array['keyword'];
		$output['absoluteness'] = shouldBeBoolean($rule['absoluteness']);
		return $output;
	}
	
	/**
	 * generate option names from ids
	 * @param string $name_string
	 * @param array(int) radio id numbers from radio table
	 * @return array(string) in the form questionName_x where x is the radio_id
	 */
	protected function makeOptionName($name_string, $radio_id_list)
	{
		$output = array();
		if (is_array($radio_id_list))
		{
			foreach ($radio_id_list as $r)
			{
				$output[] = $name_string.'_'.$r;
			}
		}
		return $output;
	}
	/**
	 * updates a particular Question in the database
	 * @param int q_id, the identity of the Question to be updated
	 * @param array of object variables: column=>value, with columns including:
	 * except for answer_array which has the format "answer_array"=>array(answer1,answer2...)
	 *	name
	 *	prompt
	 *	answer_array
	 *	correct
	 *	incorrect
	 *	unanswered
	 *	context
	 *	imponderable
	 *	general_feedback
	 *	answered
	 *	trailing_text
	 *	short_answer
	 */
	public function updateQuestion($q_id, $object_var_array)
	{
	  	// validate id
		if(!is_int($q_id) && $q_id <= 0) 
		{
			throw new InvalidArgumentException($q_id." is not a valid argument");
		}
		$table = "question";
		$settings = array();
		$var_array = array();
		$match = array();
		// this functionality duplicates Question->getIDFromName(0)
		if (isset($object_var_array["name"]))
		{
			if(preg_match("/Q([0-9]+)$/", $object_var_array["name"],$match) == 0)
			{
				$object_var_array['name'] .= "Q".$q_id;
			}
		}
		foreach($object_var_array as $key=>$value)
		{
			if ($key == "answer_array")
			{
				if (!is_array($value))
				{
					$answers = array($value);
				}
				else
				{
					$answers = $value;
				}
				$this->updateAnswer($q_id, $answers);
			}
			else
			{
				$var_array[$key] = $value;
				//$settings[] = $key."='".$this->da->escape($value)."'";
			}
		}
		$settext = implode(", ",$settings);
		$this->updateTableItem($table, $q_id, $var_array);
	}
	/**
	 * inserts or updates an answer in the database
	 * starts at multi-id 1, and updates (overwrites) if it exists, otherwise inserts
	 * then deletes extra answers if they exist
	 * @param int $q_id the question id
	 * @param array of string $answers the answer texts
	 */
	public function updateAnswer($q_id, $answers)
	{
		$result = null;
		$existing_answer_count = count($this->retrieveAnswer($q_id));
		$answer_count = count($answers);
		$answer_stack = $answers;
		$multi_id = 1;
		// update existing answers
		for ($multi_id=1; $multi_id <= $existing_answer_count; $multi_id++)
		{
			$answer = array_shift($answer_stack);
			if ($answer)
			{
				$sql = "UPDATE answer set answer ='".$this->da->escape($answer)."' WHERE Q_ID=".$this->da->escape($q_id)." AND multi_id =".$this->da->escape($multi_id);
				$result = $this->update($sql);
				
			}
			else
			{
				$sql = "DELETE from answer WHERE Q_ID=".$this->da->escape($q_id)." AND multi_id =".$this->da->escape($multi_id);
				$result = $this->update($sql);
			}
		}
		// insert excess answers
		foreach ($answer_stack as $answer)
		{
			$sql = "INSERT into answer (Q_ID, answer, multi_id) VALUES (" . $this->da->escape($q_id) . ", '" . $this->da->escape($answer) . "', " . $this->da->escape($multi_id).")";
			$result = $this->update($sql);
			$multi_id++;
		}
		return $result;
	}
	/**
	 * updates style hints for a given q_id
	 * add the hint to hint_question unless it is already there OR
	 * remove the hint from hint question if it is not in $q_style_hints
	 * @param int $q_id the question id
	 * @param array of string $style_hints the hints to add
	 * see also MEIDSQLServerDao::replaceList
	 */
	public function updateStyleHints($q_id, $style_hints)
	{
		if (is_array($style_hints))
		{
			$existing_hints = $this->suck("hint_question", "keyword", array("q_id" => $q_id));
			$already_there = array_intersect($style_hints, $existing_hints);
			$to_delete_list = array_diff($existing_hints, $already_there);
			$to_delete_list2 = array();
			foreach ($to_delete_list as $d)
			{
				$to_delete_list2[] = "'$d'";
			}
			$to_delete = implode(", ", $to_delete_list2);
			$to_add_list = array_diff($style_hints, $already_there);
			if (!empty($to_delete_list))
			{
				$sql = "DELETE FROM hint_question WHERE q_id = ". $this->da->escape($q_id) . " AND keyword in ($to_delete)";
				$result = $this->update($sql);
			}
			foreach($to_add_list as $keyword)
			{
				$this->insertTableItem("hint_question", array("q_id" => $q_id, "keyword" => $keyword));
			}
		}
	}
	/**
	 * creates a new Question in the database
	 * @param Question the Question to be created
	 * @return int the id of the question created (using getLastQ_ID())
	 */
	public function createQuestion($question)
	{
	 	$name = $question->getName();
	 	$prompt = $question->getPrompt();
	 	$correct = $question->getCorrectFeedback()->getText();
	 	$incorrect = $question->getIncorrectFeedback()->getText();
	 	$unanswered = $question->getUnansweredFeedback()->getText();
	 	$answered = $question->getAnsweredFeedback()->getText();
	 	$context = $question->getContext();
	 	$general_feedback = $question->getGeneralFeedback()->getText();
	 	$trailing_text = $question->getTrailingText();
		$short_answer = $question->getShortAnswer();
		/*
		if ($question->getShortAnswer())
		{
			$short_answer = true;
		}
		else
		{
			$short_answer = false;
		}
		*/
	 	if ($general_feedback)
	 	{
			$text = $general_feedback->getText();
			$general_feedback = $text;
	 	}
	 	// this functionality should really be in Feedback
		/* Not sure why this is here, but it doesn't make sense to me, so ...
	 	if ($answered)
	 	{
			$text = $general_feedback->getText();
			$general_feedback = $text;
	 	}
		*/
	 	$answer = $question->getAnswer();
		if (!is_array($answer))
		{
			$answer_array = array($answer);
			$answer = $answer_array;
		}
		$imponderable = $question->isImponderable();
		/**
		 * @TODO make this db compatible
		 */
		/*
	 	if ($question->isImponderable())
		{
			$imponderable = 1;
		}
		else
		{
			$imponderable = 0;
		}
		*/
		$object_var_array = array(
			"name" => $name,
			"prompt" => $prompt,
			"correct" => $correct,
			"incorrect" => $incorrect,
			"unanswered" => $unanswered,
			"context" => $context,
			"imponderable" => $imponderable,
			"general_feedback" => $general_feedback,
			"answered" => $answered,
			"trailing_text" => $trailing_text,
			"short_answer" => $short_answer
			);
		$result = $this->insertTableItem('question', $object_var_array);
	 	$q_id = $this->getLastID("question");
		$this->updateAnswer($q_id, $answer);
	 	if(get_class($question) == "RadioOptionQuestion" or get_class($question) == "CheckBoxQuestion")
	 	{
	 		$i = 1;
	 		foreach($question->getOptions() as $name=>$description)
	 		{
	 			$question->setResponse($name);
	 			$feedback = $question->getRelevantOptionFeedback();
	 			$opt_feedback = $feedback->getText();
	 			$radio_id = $i;
	 			$this->createRadioOption($q_id, $radio_id, $description, $opt_feedback);
	 			$i ++;
	 		}
	 		
	 	}
		return $result;
	}

	/**
	 * Creates a radio option entry
	 * should not be called out of the context of a question
	 */
	public function createRadioOption($q_id, $radio_id, $option_description, $option_feedback)
	{
	 	$sql = "INSERT INTO radio VALUES ('".$this->da->escape($q_id)."', '".$this->da->escape($radio_id)."', '".$this->da->escape($option_description)."', '".$this->da->escape($option_feedback)."')";
	 	$result = $this->update($sql);
		return $result;
	 }

	 /**
	  * Updates a radio option entry
	  * should not be called out of the context of a question
	  */
	 public function updateRadioOption($q_id, $radio_id, $option_description, $option_feedback)
	 {
	 	$sql = "UPDATE radio SET description = '".$this->da->escape($option_description)."', feedback = '".$this->da->escape($option_feedback)."' WHERE q_id = '".$this->da->escape($q_id)."' AND radio_id = '".$this->da->escape($radio_id)."'";
	 	$result = $this->update($sql);
		return $result;
	 }

	 /**
	  * moveRadioOption
	  * changes the radio_id and hence the order of options
	  */
	 public function moveRadioOption($q_id, $radio_id, $target_id)
	 {
	 	$sql = "UPDATE radio SET radio_id = '".$this->da->escape($target_id)."' WHERE q_id = '".$this->da->escape($q_id)."' AND radio_id = '".$this->da->escape($radio_id)."'";
	 	$result = $this->update($sql);
		return $result;
	 }
	 
	 /**
	  * Gets the latest id of a question or other table with an identity
	  * @param string table name of table having an IDENTITY column
	  * @return int id of latest IDENTITY value
	  * NB. This function is duplicated in question_dao.class.php
	  * and answerset_dao.class.php
	  * I've included it in each as an include file because I couldn't think of 
	  * a useful way to generalise it without
	  * un-abstracting the DAO superclass, which I didn't want to do.
	  * Maybe there's an alternative I haven't considered; this can be done
	  * another time.
	  * Now that this class extends MEIDSQLServerDao this function is redundant.
	
	public function getLastID($table)
	{
		// require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/get_last_id.inc.php");
		// note: mssql function only. This $sql will need to change if the db changes.
		$sql = "SELECT IDENT_CURRENT('".$this->da->escape($table)."')";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$row = $result->getRow();
		return $row[0];
	}
	  */
	 /**
	  * Adds an entry to the qset_question table
	  * @param int q_id
	  * @param int qset_id
	  * @param int category_id
	  */
	public function addToQSet($qset_id, $q_id, $category_id=null)
	{
		// validate id
		if($q_id <=0 or $qset_id <=0) 
		{
			throw new InvalidArgumentException($q_id." with ".$qset_id." is not a valid argument");
		}
		$q_count = 0;
		$sql_question_count = "SELECT count(*) FROM qset_question WHERE qset_id = " . $this->da->escape($qset_id);
		$result1 = $this->retrieve($sql_question_count);
		$result1->setFormat("NUM");
		if($result1->getNumRows() == 1)
		{
				$row = $result1->getRow();
				$q_count = (int)$row->offsetGet(0);
		}
		$var_array = array("qset_id"=>$this->da->escape($qset_id), 
		"q_id"=>$this->da->escape($q_id),  "list_order"=>$this->da->escape($q_count), "category_id"=>$this->da->escape($category_id));// list order counting from 0
		$result = $this->insertTableItem("qset_question", $var_array);
		return $result;
	 }
	/**
	 * This function retrieves sets of standard options from the database
	 * It doesn't have to be in QuestionDao, but doesn't really deserve its own DAO
	 * @return array opt_id=>summary
	 */
	public function retrieveStandardOptions()
	{
		$sql = "SELECT * FROM standard_options";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$option_set_list = array();
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				$id = $row->offsetGet("opt_id");
				$summary = $row->offsetGet("summary");
				$option_set_list[$id] = $summary;
			}
		}
		return $option_set_list;
	}
	
	/**
	 * This function returns the radio options for a given standard option set
	 * @param int opt_id
	 * @return array radio_id=>description
	 */
	public function retrieveRadioOptionsFromSet($opt_id)
	{
		$sql = "SELECT radio_id, description FROM standard_radio WHERE opt_id = '".$this->da->escape($opt_id)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$radio_list = array();
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				$id = $row->offsetGet("radio_id");
				$description = $row->offsetGet("description");
				$radio_list[$id] = $description;
			}
		}
		return $radio_list;
	}
	/**
	 * This function gets a list of all of the Standard Questions in the database
	 * @return a list of Questions with the Standard bit
	 */
	public function getStandardQuestions()
	{
		// consider abstracting this switch into a function in meid_sqlserver_dao
		switch (get_class($this->da))
		{
			case "DataAccessMssql":
			$booltype = 1;
			break;
			case "DataAccessPgsql":
			$booltype = "TRUE";
			break;
		}
		$sql = "SELECT q_id FROM question WHERE standard = $booltype";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$question_list = array();
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				$q_id = $row->offsetGet(0);
				$question_list[] = $this->getQuestionByID($q_id);	
			}
		}
		return $question_list;
	}
	 
	
	/**
	 * This function returns the radio_ids for a given Question
	 * @param int q_id, corresponds to q_id in database
	 * @return array radio_id
	 * This function is used by moveRadioOption
	 */
	public function getRadioOptions($q_id)
	{
		$sql = "SELECT radio_id FROM radio WHERE Q_ID = '".$this->da->escape($q_id)."' order by radio_id";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$radio_list = array();
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				$id = $row->offsetGet(0);
				$radio_list[] = $id;
			}
		}
		return $radio_list;
	}	
}
?>

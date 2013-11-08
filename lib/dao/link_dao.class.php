<?php
/**
 * Link DAO
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
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/core/link.class.php");

/**
 * LinkDao (Database Access Object)
 * based on daophp5
 * allows creation, retrieval, updating and deletion of links in the database
 * 
 * @package dao
 * @todo modernise these functions to call meid_sqlserver_dao generic functions
 */
class LinkDao extends MEIDSQLServerDao
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
	 * getLinkByKeyword
	 * @param string keyword, a single word, limit 30 chars to identify the link
	 * @return Link object
	 * pretty similar to getUrlByKeyword below, which this function supercedes
	 */
	public function getLinkByKeyword($keyword)
	{
		$sql = "SELECT name, url FROM link WHERE keyword ='".$this->da->escape($keyword)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$linkinfo = array("name"=>"", "url"=>"");
		if($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			$linkinfo['name'] = $row->offsetGet("name");
			$linkinfo['url'] = $row->offsetGet("url");
			$link = new Link($keyword,$linkinfo['name'],$linkinfo['url']);
		}
		else
		{
			$link = false;
		}
		return $link;
	}
	/**
	 * getUrlByKeyword
	 * @param string keyword, a single word, limit 30 chars to identify the link
	 * @return array linkinfo:
	 *	"name"=>the text of the link
	 *	"url"=>the url of the link
	 */
	public function getUrlByKeyword($keyword)
	{
		return $this->pluck('link', null, array('keyword' => $keyword), '*');
		/*
		$sql = "SELECT name, url FROM link WHERE keyword ='".$this->da->escape($keyword)."'";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$linkinfo = array("name"=>"", "url"=>"");
		if($result->getNumRows() == 1) 
		{
			
			$row = $result->getRow();
			$linkinfo['name'] = $row->offsetGet("name");
			$linkinfo['url'] = $row->offsetGet("url");
		}
		
		return $linkinfo;
		*/
	}
	/**
	 * getTagsByKeyword
	 * retrieves tags associated with a link
	 * @param bool $ids_only, returns the tag_id instead of the description. Default FALSE
	 * @return array(strings)
	 * Note that there isn't a standardised way to do joins in MEIDSQLserverDao
	 */
	public function getTagsByKeyword($keyword, $ids_only = false)
	{
		$tags = array();
		$sql = "SELECT t.description, t.tag_id
		from tag t, link_tag lt 
		where lt.tag_id = t.tag_id
		and lt.keyword =". $this->da->escapeContext($keyword);
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				if ($ids_only === true)
				{
					$id_type = "tag_id";
				}
				else
				{
					$id_type = "description";
				}
				$tags[] = $row->offsetGet($id_type);
			}
		}
		return $tags;
	}
	
	/**
	 * retrieveLinks
	 * gets all of the links in the database
	 * @return an array of keywords
	 */
	public function retrieveKeywordList()
	{
		$keywords = array();
		/*
		$sql = "SELECT keyword from link";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		
		if($result->getNumRows() >= 1)
		{
			foreach($result as $row)
			{
				$keywords[] = $row->offsetGet("keyword");
			}
		}
		*/
		$keywords_array = $this->suck('link', 'keyword', null, 'keyword');
		if (isset($keywords_array))
		{
			$keywords = $keywords_array;
		}
		return $keywords;
	}
	
	/**
	 * update link
	 * updates a particular link in the database
	 * @param string keyword, the link identity
	 * @param array of object variables: column=>value, with columns including:
	 * 	name, the default text to display
	 * 	url, the url of the link
	 */
	public function updateLink($keyword, $object_var_array)
	{
		$result = $this->updateTableItem('link', null, $object_var_array, array('keyword'=>$keyword));
		/*
		$settings = array();
		foreach($object_var_array as $key=>$value)
		{
			$settings[] = $key."='".$this->da->escape($value)."'";
		}
		$settext = implode(", ",$settings);
		$sql = "UPDATE link SET ".$settext." WHERE keyword ='".$this->da->escape($keyword)."'";
		$result = $this->update($sql);
		*/
		return $result;
	}
	
	/**
	 * create link
	 *
	 * creates a particular link in the database
	 * @param Link the link to create
	 */
	public function createLink($link)
	{
		// validate id
		if(get_class($link) != "Link") 
		{
			throw new InvalidArgumentException($link." is not a valid argument");
		}
		$keyword = $link->getKeyword();
		$name = $link->getName();
		$url = $link->getUrl();
		$var_array = array(
			'keyword' => $keyword,
			'name' => $name,
			'url' => $url
			);
		$result = $this->insertTableItem('link', $var_array);
		/*
		$sql = "INSERT INTO link (keyword, name, url) VALUES('".$this->da->escape($keyword)."',
		'".$this->da->escape($name)."',
		'".$this->da->escape($url)."')";
		$result = $this->update($sql);
		*/
		return $result;
	}
}
?>

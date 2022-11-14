<?php
/*******
 * @package xbBooks
 * @filesource admin/models/persons.php
 * @version 0.9.10.2 14th November 2022
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;

class XbbooksModelPersons extends JModelList {
	
	protected $xbfilmsStatus;
	
    public function __construct($config = array()) {
        
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
            		'id', 'a,id',
            		'firstname', 'lastname',
            		'published', 'a.state',
            		'ordering', 'a.ordering',
            		'category_title', 'c.title',
            		'catid', 'a.catid', 'category_id',
            		'sortdate' );
        }
        
        $this->xbfilmsStatus = Factory::getSession()->get('xbfilms_ok',false);
        parent::__construct($config);
    }
    
    protected function getListQuery() {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        
        $query->select('a.id AS id, a.firstname AS firstname, a.lastname AS lastname, a.alias AS alias, 
			a.summary AS summary, a.portrait AS portrait, a.biography AS biography, a.ext_links AS ext_links,
			a.nationality AS nationality, a.year_born AS year_born, a.year_died AS year_died,
			a.catid AS catid, a.state AS published, a.created AS created, a.created_by AS created_by, 
			a.created_by_alias AS created_by_alias, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, 
            a.metadata AS metadata, a.ordering AS ordering, a.params AS params, a.note AS note');
        $query->select('IF((year_born>-9999),year_born,year_died) AS sortdate');
            
        $query->from($db->quoteName('#__xbpersons','a'));
        
        $query->select('(SELECT COUNT(DISTINCT(bp.book_id)) FROM #__xbbookperson AS bp WHERE bp.person_id = a.id) AS bcnt');
        if ($this->xbfilmsStatus) $query->select('(SELECT COUNT(DISTINCT(fp.film_id)) FROM #__xbfilmperson AS fp WHERE fp.person_id = a.id) AS fcnt');
        
        $query->join('LEFT',$db->quoteName('#__xbbookperson', 'b') . ' ON ' . $db->quoteName('b.person_id') . ' = ' .$db->quoteName('a.id'));
        
        //        $query->select('(GROUP_CONCAT(b.book_id SEPARATOR '.$db->quote(',') .')) AS booklist');
//        $query->join('LEFT',$db->quoteName('#__xbbookperson', 'b') . ' ON ' .$db->quoteName('b.person_id') . ' = ' . $db->quoteName('a.id'));
        
        $query->select('c.title AS category_title')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
            // Filter: like / search
        $search = $this->getState('filter.search');
        
        if (!empty($search)) {
            if (stripos($search, 'i:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 2));
            } elseif (stripos($search, 'b:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search,2)), true) . '%'));
                $query->where('(summary LIKE ' . $search.' OR biography LIKE '.$search.')');
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(lastname LIKE ' . $search.' OR firstname LIKE '.$search.')');               
            }
        }
        
        // Filter by published state
        $published = $this->getState('filter.published');
        
        if (is_numeric($published)) {
            $query->where('state = ' . (int) $published);
//        } elseif ($published === '') {
//            $query->where('(state IN (0, 1))');
        }
        
        //filter by nationality
        $natfilt = $this->getState('filter.nationality');
        if (!empty($natfilt)) {
            $query->where('a.nationality = '.$db->quote($natfilt));
        }
        
        //Filter by role
        $rolefilt = $this->getState('filter.rolefilt');
        if (empty($rolefilt)) { $rolefilt = 'book'; }
        if ($rolefilt != 'all') {
        	if ($rolefilt == 'book') {
        		$query->where('b.id IS NOT NULL');
        	} elseif ($rolefilt == 'notbook') {
        		$query->where('b.id IS NULL');
        	} elseif ($rolefilt == 'orphans') {
        		if ($this->xbfilmsStatus) {
        			$query->join('LEFT OUTER',$db->quoteName('#__xbfilmperson', 'f') . ' ON ' .$db->quoteName('a.id') . ' = ' . $db->quoteName('f.person_id'));
        			$query->where('f.id IS NULL');
        		}
        		$query->where('b.id IS NULL');
        	} else {
        		$query->where('b.role = '.$db->quote($rolefilt));
        	}
        }
        
        // Filter by category.
        $app = Factory::getApplication();
        $categoryId = $app->getUserStateFromRequest('catid', 'catid','');
        $app->setUserState('catid', '');
        if ($categoryId=='') {
        	$categoryId = $this->getState('filter.category_id');
        }
//        $subcats=0;
        if (is_numeric($categoryId))
        {
        	$query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
        }
        
        //filter by tags
        $tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        if (!empty($tagId)) {
        	$tagfilt = array(abs($tagId));
        	$taglogic = $tagId>0 ? 0 : 2;
        } else {
        	$tagfilt = $this->getState('filter.tagfilt');
        	$taglogic = $this->getState('filter.taglogic');  //0=ANY 1=ALL 2= None
        }
        
        if (empty($tagfilt)) {
            $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
 					WHERE type_alias LIKE '.$db->quote('com_xbpeople.person').')';
            if ($taglogic === '1') {
                $query->where('a.id NOT IN '.$subQuery);
            } elseif ($taglogic === '2') {
                $query->where('a.id IN '.$subQuery);
            }
        } else {
            $tagfilt = ArrayHelper::toInteger($tagfilt);
            $subquery = '(SELECT tmap.tag_id AS tlist FROM #__contentitem_tag_map AS tmap
                WHERE tmap.type_alias = '.$db->quote('com_xbpeople.person').'
                AND tmap.content_item_id = a.id)';
            switch ($taglogic) {
                case 1: //all
                    for ($i = 0; $i < count($tagfilt); $i++) {
                        $query->where($tagfilt[$i].' IN '.$subquery);
                    }
                    break;
                case 2: //none
                    for ($i = 0; $i < count($tagfilt); $i++) {
                        $query->where($tagfilt[$i].' NOT IN '.$subquery);
                    }
                    break;
                default: //any
                    if (count($tagfilt)==1) {
                        $query->where($tagfilt[0].' IN '.$subquery);
                    } else {
                        $tagIds = implode(',', $tagfilt);
                        if ($tagIds) {
                            $subQueryAny = '(SELECT DISTINCT content_item_id FROM #__contentitem_tag_map
                                WHERE tag_id IN ('.$tagIds.') AND type_alias = '.$db->quote('com_xbpeople.person').')';
                            $query->innerJoin('(' . (string) $subQueryAny . ') AS tagmap ON tagmap.content_item_id = a.id');
                        }
                    }
                    break;
            }
        } //end if $tagfilt
        
        // Add the list ordering clause.
        $orderCol	= $this->state->get('list.ordering', 'lastname');
        $orderDirn 	= $this->state->get('list.direction', 'asc');
        if ($orderCol == 'a.ordering' || $orderCol == 'a.catid') {
        	$orderCol = 'category_title '.$orderDirn.', a.ordering';
        }
        
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        if ($orderCol != 'lastname') {
	        $query->order('lastname ASC');
        }
        
        $query->group('a.id');
        
        return $query;
    }
    
    public function getItems() {
        $items  = parent::getItems();
        // we are going to add the list of people (with roles) for teach book
        //and apply any book title filter
        $tagsHelper = new TagsHelper;
        
        foreach ($items as $i=>$item) { 
            
            $item->books = XbcultureHelper::getPersonBooks($item->id);
            
            $roles = array_column($item->books,'role');
            $item->acnt = count(array_keys($roles, 'author'));
            $item->ecnt = count(array_keys($roles, 'editor'));
            $item->mcnt = count(array_keys($roles, 'mention'));
            $item->ocnt = count(array_keys($roles, 'other'));
            
            $item->alist = $item->acnt==0 ? '' : XbcultureHelper::makeLinkedNameList($item->books,'author','ul',true,1);
            $item->elist = $item->ecnt==0 ? '' : XbcultureHelper::makeLinkedNameList($item->books,'editor','ul',true,1);
            $item->mlist = $item->mcnt==0 ? '' : XbcultureHelper::makeLinkedNameList($item->books,'mention','ul',true,1);
            $item->olist = $item->ocnt==0 ? '' : XbcultureHelper::makeLinkedNameList($item->books,'other','ul',true,1);
            
            $item->ext_links = json_decode($item->ext_links);
            $item->ext_links_list ='';
            $item->ext_links_cnt = 0;
            if(is_object($item->ext_links)) {
                $item->ext_links_cnt = count((array)$item->ext_links);
                foreach($item->ext_links as $lnk) {
                    $item->ext_links_list .= '<a href="'.$lnk->link_url.'" target="_blank">'.$lnk->link_text.'</a>, ';
                }
                $item->ext_links_list = trim($item->ext_links_list,', ');
            
 //           $item->books = XbbooksGeneral::getPersonRoleArray($item->id,'',true);
 //           $cnts = array_count_values(array_column($item->books, 'role'));
 //           $item->acnt = (key_exists('author',$cnts))?$cnts['author'] : 0;
//            $item->ecnt = (key_exists('editor',$cnts))?$cnts['editor'] : 0;
 //           $item->mcnt = (key_exists('mention',$cnts))?$cnts['mention'] : 0;
//            $item->ocnt = (key_exists('other',$cnts))?$cnts['other'] : 0;;
  
            
//             $item->filmcnt = 0;
//             if ($this->xbfilmsStatus) {
//             	$db    = Factory::getDbo();
//             	$query = $db->getQuery(true);
//             	$query->select('COUNT(*)')->from('#__xbfilmperson');
//             	$query->where('person_id = '.$db->quote($item->id));
//             	$db->setQuery($query);
//             	$item->filmcnt = $db->loadResult();
//             }
    
//             $item->alist='';
//             if ($item->acnt>0) {
//             	$item->alist = htmlentities(XbbooksGeneral::makeLinkedNameList($item->books,'author',', ',false,true));
//             }
//             $item->elist='';
//             if ($item->ecnt>0) {
//             	$item->elist = htmlentities(XbbooksGeneral::makeLinkedNameList($item->books,'editor',', ',false,true));
//             }
//             $item->mlist='';
//             if ($item->mcnt>0) {
//             	$item->mlist = htmlentities(XbbooksGeneral::makeLinkedNameList($item->books,'mention',', ',false,true));
//             }
//             $item->olist='';
//             if ($item->ocnt>0) {
//             	$item->olist = htmlentities(XbbooksGeneral::makeLinkedNameList($item->books,'other',', ',false,true,true));
//            }
            
	        } //end if is_object
	        $item->persontags = $tagsHelper->getItemTags('com_xbpeople.person' , $item->id);
        } //end foreach item
	    return $items;
    }

}
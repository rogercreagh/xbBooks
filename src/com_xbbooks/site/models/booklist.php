<?php
/*******
 * @package xbBooks
 * @filesource site/models/booklist.php
 * @version 0.9.8.2 17th May 2022
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Categories;
use Joomla\CMS\Helper\TagsHelper;

class XbbooksModelBooklist extends JModelList {
	
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array ('title', 'a.title',
					'pubyear','a.pubyear', 'fiction',
					'averat', 'sort_date', 'last_read', 'a.last_read',
					'catid', 'a.catid', 'category_id',
					'category_title' );
		}
		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null) {
		$app = Factory::getApplication('site');
		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);
		
		$categoryId = $app->getUserStateFromRequest('catid', 'catid','');
		$app->setUserState('catid', '');
		$this->setState('categoryId',$categoryId);
		$tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
		$app->setUserState('tagid', '');
		$this->setState('tagId',$tagId);
		
		parent::populateState($ordering, $direction);
		
		//pagination limit
		$limit = $this->getUserStateFromRequest($this->context.'.limit', 'limit', 25 );
		$this->setState('limit', $limit);
		$this->setState('list.limit', $limit);
		$limitstart = $app->getUserStateFromRequest('limitstart', 'limitstart', $app->get('start'));
		$this->setState('list.start', $limitstart);
		
	}
	
	protected function getListQuery() {
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->select('a.id AS id, a.title AS title, a.subtitle AS subtitle, a.alias AS alias,
            a.summary AS summary, a.pubyear AS pubyear, a.catid AS catid, a.fiction AS fiction,
            a.cover_img AS cover_img, a.synopsis AS synopsis, a.state AS published,
            a.created AS created, a.acq_date AS acq_date, a.last_read AS last_read,
            a.created_by_alias AS created_by_alias,
            a.ordering AS ordering, a.params AS params');
//            ->select('(GROUP_CONCAT(p.person_id SEPARATOR '.$db->quote(',') .')) AS personlist');
            $query->from('#__xbbooks AS a')
            	->join('LEFT OUTER',$db->quoteName('#__xbbookperson', 'p') . ' ON ' .$db->quoteName('a.id') . ' = ' . $db->quoteName('p.book_id'))
            	->join('LEFT OUTER',$db->quoteName('#__xbbookcharacter', 'ch') . ' ON ' .$db->quoteName('a.id') . ' = ' . $db->quoteName('ch.book_id')); 
            	//->join('LEFT', $db->quoteName('#__xbbookreviews', 'r').' ON r.book_id = a.id');
            $query->select('c.title AS category_title');
            $query->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
//           $query->select('(SELECT COUNT(*) FROM #__xbbookreviews AS br WHERE br.book_id=a.id AND br.state=1) AS revcnt');
            $query->select('(SELECT AVG(br.rating) FROM #__xbbookreviews AS br WHERE br.book_id=a.id) AS averat');
//          $query->select('(SELECT MAX(r.rev_date) FROM #__xbbookreviews AS r WHERE r.book_id=a.id) AS lastread');
            $query->select('GREATEST(a.acq_date, COALESCE(a.last_read, 0)) AS sort_date');
            
            // Filter by published state, we only show published items in front end. Both item and its category must be published.
            $query->where('a.state = 1');
            $query->where('c.published = 1');
            
            // Search in title/id/synop
            $search = $this->getState('filter.search');            
            if (!empty($search)) {
            	if (stripos($search,'s:')===0) {
            		$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search,2)), true) . '%'));
            		$query->where('(a.synopsis LIKE ' . $search.' OR a.summary LIKE '.$search.')');
            	} else {
            		$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            		$query->where('(a.title LIKE ' . $search . ' OR a.subtitle LIKE ' . $search . ')');
            	}
            }
            
             $searchbar = (int)$this->getState('params',0)['search_bar'];
            //if a menu filter is set this takes priority and serch filter field is hidden
 
           // Filter by category and subcats 
             $categoryId = $this->getState('categoryId');
             $this->setState('categoryId','');
             $dosubcats = 0;
             if (empty($categoryId)) {
	            $categoryId = $this->getState('params',0,'int')['menu_category_id'];
	            $dosubcats=$this->getState('params',0)['menu_subcats'];            	
             }
            if (($searchbar==1) && ($categoryId==0)){
            	$categoryId = $this->getState('filter.category_id');
            	$dosubcats=$this->getState('filter.subcats');
            }
            if ($categoryId > 0) {
            	if ($dosubcats) {
            		$catlist = $categoryId;
            		$subcatlist = XbbooksHelper::getChildCats($categoryId,'com_xbbooks');
            		if ($subcatlist) { $catlist .= ','.implode(',',$subcatlist);}
            		$query->where('a.catid IN ('.$catlist.')');
            	} else {
            		$query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
            	}
            }
            
            //filter by fiction/non
            $fiction = (int)$this->getState('params',0)['menu_fiction'];
            if (($searchbar==1) && ($fiction==0)) { //look for filter setting
	            $fiction = (int)$this->getState('filter.fictionfilt',0);
            }
            if ($fiction > 0) {
            	$filtstr = $fiction == 2 ? 'TRUE' : 'FALSE';
            	$query->where('a.fiction = '. $filtstr);
            }
            //filter by read/unread
            $readfilt = $this->getState('filter.readfilt');
            if ((int)$readfilt==1) {
                $query->where('a.last_read > 0');
            } elseif ($readfilt==2) {
                $query->where('COALESCE(a.last_read,0) = 0');
            }
            
            //filter by person 
            $pfilt = $this->getState('params')['menu_perfilt'];
            $prole = $this->getState('params')['menu_prole'];
            if (($searchbar==1) && ($pfilt==0)) { 	//look for filter setting
            	$pfilt = $this->getState('filter.perfilt');
            	$prole = $this->getState('filter.prole');
            }
            if ((int)$pfilt>0) {
            	$query->where('p.person_id = '.$pfilt);
            	if ($prole == 1 ) { $query->where('p.role = '.$db->quote('author'));}
            	if ($prole == 2 ) { $query->where('p.role = '.$db->quote('editor'));}
            	if ($prole == 4 ) { $query->where('p.role = '.$db->quote('mention'));}
            }
            
            //filter by character
            $chfilt = $this->getState('params')['menu_charfilt'];
            if (($searchbar==1) && ($chfilt==0)) { 	//look for filter setting
            	$chfilt = $this->getState('filter.charfilt');
            }
            if ((int)$chfilt>0) {
            	$query->where('ch.char_id = '.$chfilt);
            }
            
            //filter by tag
            $tagfilt = $this->getState('tagId');
//            $this->setState('tagId','');
            $taglogic = 0;
            if (empty($tagfilt)) {
                $tagfilt = $this->getState('params')['menu_tag'];
                $taglogic = $this->getState('params')['taglogic']; //1=AND otherwise OR           	
            }
                       
            if (($searchbar==1) && (empty($tagfilt))) { 
            //look for filter options and ignore menu options 
	            $tagfilt = $this->getState('filter.tagfilt');
	            $taglogic = $this->getState('filter.taglogic'); //1=AND otherwise OR
            }
            // Run simplified query when filtering by one tag.
            if (is_array($tagfilt) && count($tagfilt) === 1) {
            	$tagfilt = $tagfilt[0];
            }           
            if ($tagfilt && is_array($tagfilt)) {
            	$tagfilt = ArrayHelper::toInteger($tagfilt);            	
            	if ($taglogic) { //AND logic
            		/***
            		 SELECT id FROM j3tst_xbbooks AS a
            		 INNER JOIN j3tst_contentitem_tag_map AS tagmap
            		 ON tagmap.content_item_id  = a.id
            		 INNER JOIN j3tst_contentitem_tag_map AS tagmap2
            		 ON tagmap2.content_item_id  = a.id
            		 WHERE tagmap.tag_id = 5 AND tagmap.type_alias = 'com_xbbooks.book'
            		 AND tagmap2.tag_id = 2 AND tagmap2.type_alias = 'com_xbbooks.book'
            		 ***/
            		for ($i = 0; $i < count($tagfilt); $i++) {
            			$mapname = 'tagmap'.$i;
            			$query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', $mapname).
            					' ON ' . $db->quoteName($mapname.'.content_item_id') . ' = ' . $db->quoteName('a.id'));
            			$query->where( array(
            							$db->quoteName($mapname.'.tag_id') . ' = ' . $tagfilt[$i],
            							$db->quoteName($mapname.'.type_alias') . ' = ' . $db->quote('com_xbbooks.book'))
            					);
            		}
            	} else { //OR logic           		
            		$subQuery = $db->getQuery(true)
            		->select('DISTINCT ' . $db->quoteName('content_item_id'))
            		->from($db->quoteName('#__contentitem_tag_map'))
            		->where(
            				array(
            						$db->quoteName('tag_id') . ' IN (' . implode(',', $tagfilt) . ')',
            						$db->quoteName('type_alias') . ' = ' . $db->quote('com_xbbooks.book'),
            				)
            				);
            		
            		$query->join(
            				'INNER',
            				'(' . $subQuery . ') AS ' . $db->quoteName('tagmap')
            				. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            				);
            		
            	}
            	
            } elseif ($tag = (int) $tagfilt) {
            	$query->join(
            			'INNER',
            			$db->quoteName('#__contentitem_tag_map', 'tagmap')
            			. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            			)
            			->where(
            					array(
            							$db->quoteName('tagmap.tag_id') . ' = ' . $tagfilt,
            							$db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_xbbooks.book')
            					)
            					);
            } //endif tagfilt
            
            
            // Add the list ordering clause.
            $orderCol       = $this->state->get('list.ordering', 'sort_date');
            $orderDirn      = $this->state->get('list.direction', 'DESC');
            switch($orderCol) {
            	case 'a.ordering' :
            	case 'a.catid' :
            		//needs a menu option to set orderCol to ordering. Also menu option to alllow user to reorder on table
            		$query->order('category_title '.$orderDirn.', a.ordering');
            		break;
            	case 'category_title':
            		$query->order('category_title '.$orderDirn.', title');
            		break;
            	default:
            		$query->order($db->escape($orderCol.' '.$orderDirn));
            		break;
            }
            
            $query->group('a.id');            
            return $query;
	}
	
	public function getItems() {
		$items  = parent::getItems();
		$tagsHelper = new TagsHelper;
		
		$app = Factory::getApplication();
		$bks = array();
		for ($i = 0; $i < count($items); $i++) {
		    $bks[$i] = $items[$i]->id;
		}
		$app->setUserState('books.sortorder', $bks);
		
		
		foreach ($items as $i=>$item) {
		    $item->people = XbbooksGeneral::getBookRolesArray($item->id,'',false);
			$cnts = array_count_values(array_column($item->people, 'role'));
			$item->authcnt = (key_exists('author',$cnts))? $cnts['author'] : 0;
			$item->editcnt = (key_exists('editor',$cnts))? $cnts['editor'] : 0;
			$item->mencnt = (key_exists('mention',$cnts))? $cnts['mention'] : 0;
			
			$item->chars = XbbooksHelper::getCharacterBooksArray($item->id);
			$item->charcnt = count($item->chars);
			
			$item->reviews = XbbooksGeneral::getBookReviews($item->id);
			$item->revcnt = count($item->reviews);
//			$item->lastseen = 0;
//			if ($item->revcnt>0) {
//			    $item->lastseen = max(array_column($item->reviews,'seen_date'));
//			}
			
			//make author/editor lists
			$item->alist = $item->authcnt==0 ? '' : XbbooksGeneral::makeLinkedNameList($item->people,'author',',', (($item->editcnt)==0)? true:false) ;
			$item->elist = $item->editcnt==0 ? '' : XbbooksGeneral::makeLinkedNameList($item->people,'editor',',');
			
			if (($item->charcnt)==0){
				$item->clist = '';
			} elseif ($item->charcnt == 1) {
				$item->clist = XbbooksGeneral::makeLinkedNameList($item->chars,'',', ',true);
			} else {
				$item->clist = XbbooksGeneral::makeLinkedNameList($item->chars,'',', ',false);
			}
			if (($item->mencnt)==0){
				$item->mlist = '';
			} elseif ($item->mencnt == 1) {
				$item->mlist = XbbooksGeneral::makeLinkedNameList($item->people,'mention',', ',true);
			} else {
				$item->mlist = XbbooksGeneral::makeLinkedNameList($item->people,'mention',', ',false);
			}
						
			$item->tags = $tagsHelper->getItemTags('com_xbbooks.book' , $item->id);			
			
		} //foreach item
		return $items;
	}	
		
}

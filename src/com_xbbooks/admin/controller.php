<?php
/*******
 * @package xbBooks
 * @filesource admin/controller.php
 * @version 0.9.0 7th April 2021
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

class XbbooksController extends JControllerLegacy {
    
	protected $default_view = 'cpanel';
	
	public function display ($cachable = false, $urlparms = false){
//	    require_once JPATH_COMPONENT.'/helpers/xbbooks.php';
//	    require_once JPATH_COMPONENT.'/helpers/xbbooksgeneral.php';
//	    require_once JPATH_ADMINISTRATOR . '/components/com_xbpeople/helpers/xbculture.php';
	    
		return parent::display();
	}
}


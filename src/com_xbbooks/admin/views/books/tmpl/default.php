<?php
/*******
 * @package xbBooks
 * @filesource admin/views/books/tmpl/default.php
 * @version 1.0.3.2 9th January 2023
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => Text::_('XBCULTURE_SELECT_TAGS')));
HTMLHelper::_('formbehavior.chosen', '.multipleCats', null, array('placeholder_text_multiple' => Text::_('XBCULTURE_SELECT_CATS')));
HTMLHelper::_('formbehavior.chosen', 'select');

$user   = Factory::getUser();
$userId         = $user->get('id');
$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape(strtolower($this->state->get('list.direction')));
if (!$listOrder) {
    $listOrder='last_read';
    $listDirn = 'descending';
}
$orderNames = array('title'=>Text::_('XBCULTURE_TITLE'),'pubyear'=>Text::_('XBBOOKS_PUBYEARCOL'),
	'id'=>'id','first_read'=>Text::_('XBBOOKS_FIRST_READ'),'last_read'=>Text::_('XBBOOKS_LAST_READ'),'category_title'=>Text::_('XBCULTURE_CATEGORY'),
    'published'=>Text::_('XBCULTURE_STATUS'),'ordering'=>Text::_('XBCULTURE_ORDERING'),'a.created'=>Text::_('XBCULTURE_DATE_ADDED')
);


$saveOrder      = $listOrder == 'ordering';
$canOrder       = $user->authorise('core.edit.state', 'com_xbbooks.book');
if ($saveOrder) {
	$saveOrderingUrl = 'index.php?option=com_xbbooks&task=books.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'xbbooksList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}


$nocover = "media/com_xbbooks/images/nocover.jpg";
$nofile = "media/com_xbbooks/images/nofile.jpg";

$belink = 'index.php?option=com_xbbooks&view=book&task=book.edit&id=';
$relink = 'index.php?option=com_xbbooks&view=review&task=review.edit&id=';
$rvlink = 'index.php?option=com_xbbooks&view=review&task=review.edit&id='; //change this to view view when available
$cvlink = 'index.php?option=com_xbbooks&view=category&id=';
$tvlink = 'index.php?option=com_xbbooks&view=tag&id=';

?>
<style type="text/css" media="screen">
    .xbpvmodal .modal-body iframe { max-height:calc(100vh - 190px);}
</style>
<form action="<?php echo Route::_('index.php?option=com_xbbooks&view=books'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty( $this->sidebar)) : ?>
        <div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
	<?php else : ?>
        <div id="j-main-container">
	<?php endif;?>
 	<div class="pull-right span6 xbtr xbm0">
 			<?php $fnd = $this->pagination->total;
			echo $fnd .' '. Text::_(($fnd==1)?'XBCULTURE_BOOK':'XBCULTURE_BOOKS').' '.Text::_('XBCULTURE_FOUND').', ';
			?>
            <?php echo 'sorted by '.$orderNames[$listOrder].' '.$listDirn ; ?>
	</div>
	<div class="clearfix"></div>
    <div class="pull-right pagination xbm0" style="padding-left:10px;">
        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
	<?php // Search tools bar
        echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
    ?>
	<div class="clearfix"></div>	
	<?php $search = $this->searchTitle; ?>
	<?php if ($search) : ?>
		<?php echo '<p>Searched for <b>'; ?>
		<?php if (stripos($search, 'i:') === 0) {
                echo trim(substr($search, 2)).'</b> '.Text::_('XBBOOKS_AS_BOOKID');
            } elseif (stripos($search, 's:') === 0) {
                echo trim(substr($search, 2)).'</b> '.Text::_('XBBOOKS_AS_INSYNOP');
            } else {
				echo trim($search).'</b> '.Text::_('XBBOOKS_AS_INTITLE');
			}
			echo '</p>';
        ?>	
	<?php endif; ?> 

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>	
		<table class="table table-striped table-hover" style="table-layout:fixed;" id="xbbooksList">	
			<thead>
				<tr>
					<th class="nowrap center hidden-phone" style="width:25px;">
						<?php echo HTMLHelper::_('searchtools.sort', '', 'ordering', 
						    $listDirn, $listOrder, null, 'asc', 'XBCULTURE_HEADING_ORDERING_DESC', 'icon-menu-2'); ?>
					</th>
					<th class="hidden-phone center" style="width:25px;">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th class="nowrap center" style="width:55px">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
					</th>
					<th class="center" style="width:80px">
						<?php echo Text::_( 'XBBOOKS_COVER' ); ?>
					</th>			
					<th>
						<?php echo HTMLHelper::_('searchtools.sort','XBCULTURE_TITLE','title',$listDirn,$listOrder).
    						' <span class="xb095;">'.
     						Text::_('XBCULTURE_AUTHOR').', '.
     						HTMLHelper::_('searchtools.sort','XBBOOKS_PUBYEARCOL','pubyear',$listDirn,$listOrder ).' '.
    					   '</span>';
						?>
					</th>					
					<th>
						<?php echo Text::_('XBCULTURE_SUMMARY');?>
					</th>
					<th class="hidden-phone" style="width:15%;">
						<?php echo Text::_('XBCULTURE_REVIEWS_U'); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort','First','first_read',$listDirn,$listOrder ).'/'; 
						echo HTMLHelper::_('searchtools.sort','Last','last_read',$listDirn,$listOrder ).' read'; ?>					    
					</th>
					<th class="hidden-tablet hidden-phone" style="width:15%;">
						<?php echo HTMLHelper::_('searchtools.sort','XBCULTURE_CATS','category_title',$listDirn,$listOrder ).' &amp; ';						
						echo Text::_( 'XBCULTURE_TAGS_U' ); ?>
					</th>
					<th class="nowrap hidden-tablet hidden-phone" style="width:45px;">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder );?>
					</th>
    				<th>[pv]</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->items as $i => $item) :
                $canEdit    = $user->authorise('core.edit', 'com_xbbooks.book.'.$item->id);
                $canCheckin = $user->authorise('core.manage', 'com_checkin') 
                                        || $item->checked_out==$userId || $item->checked_out==0;
				$canEditOwn = $user->authorise('core.edit.own', 'com_xbbooks.book.'.$item->id) && $item->created_by == $userId;
                $canChange  = $user->authorise('core.edit.state', 'com_xbbooks.book.'.$item->id) && $canCheckin;
			?>
				<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">	
					<td class="order nowrap center hidden-phone">
                        <?php
                            $iconClass = '';
                            if (!$canChange) {
                                $iconClass = ' inactive';
                            } elseif (!$saveOrder) {
                                $iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::tooltipText('JORDERINGDISABLED');
                            }
                        ?>
                        <span class="sortable-handler<?php echo $iconClass; ?>">
                        	<span class="icon-menu" aria-hidden="true"></span>
                        </span>
                        <?php if ($canChange && $saveOrder) : ?>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
                        <?php endif; ?>
					</td>
					<td class="center hidden-phone">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center">
						<div class="btn-group">
							<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'book.', $canChange, 'cb'); ?>
							<?php if ($item->note!=""){ ?>
								<span class="btn btn-micro active hasTooltip" title="" data-original-title="<?php echo '<b>'.Text::_( 'XBCULTURE_NOTE' ) .'</b>: '. htmlentities($item->note); ?>">
									<i class="icon- xbinfo"></i>
								</span>
							<?php } else {?>
								<span class="btn btn-micro inactive" style="visibility:hidden;" title=""><i class="icon-info"></i></span>
							<?php } ?>
						</div>
					</td>
					<td>
						<?php if(!empty($item->cover_img)) : ?>
						<img class="img-polaroid hasTooltip xbimgthumb" title="" 
							data-original-title="<?php echo $item->cover_img;?>"
							<?php 
    							$src = $item->cover_img;
    							if (!file_exists(JPATH_ROOT.'/'.$src)) {
    							    $src = $nofile;
    							}
    							$src = Uri::root().$src;
							?>
							src="<?php echo $src; ?>"
							border="0" alt="" />	
						<?php endif; ?>					
					</td>
					<td>
						<p class="xbtitlelist">
						<?php if ($item->checked_out) {
						    $couname = Factory::getUser($item->checked_out)->username;
						    echo HTMLHelper::_('jgrid.checkedout', $i, Text::_('XBCULTURE_OPENEDBY').': '.$couname, $item->checked_out_time, 'book.', $canCheckin);
						} ?>
						<?php if ($canEdit || $canEditOwn) : ?>
							<a href="<?php echo Route::_($belink.$item->id);?>" <?php echo ($item->published<>1) ? 'class="xbhlt"' : ''; ?>
								title="<?php echo Text::_('XBCULTURE_EDIT'); ?>" >
								<b><?php echo $this->escape($item->title); ?></b></a> 
						<?php else : ?>
							<?php echo $this->escape($item->title); ?>
						<?php endif; ?>
						<?php if (!empty($item->subtitle)) {
                          echo '<br /><span class="xbnorm xb09">'.$this->escape($item->subtitle).'</span>';
                        } ?>
                        <br />                        
						<?php $alias = Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
                        <span class="xbnit xb08"><?php echo $alias;?></span>
						<br /><span class="xb09">
						<?php if ($item->editcnt>0) : ?>
							<span class="xbnit">
								<?php echo Text::_($item->editcnt>1 ? 'XBCULTURE_EDITORS' : 'XBCULTURE_EDITOR' ); ?>
							: </span>
							<span class="hasTooltip"  style="margin:0;" title data-original-title="
								<?php echo ($item->editcnt>0)? Text::_('XBCULTURE_AUTHOR').': '.strip_tags($item->authlist) : ''; ?>
								"> 
								<?php echo $item->editlist; ?>
							</span>						
						<?php elseif ($item->authcnt>0) : ?>
							<span class="xbnit"><?php echo Text::_($item->authcnt>1 ? 'XBCULTURE_AUTHORS' : 'XBCULTURE_AUTHOR' ); ?>: </span>
							<?php echo $item->authlist; ?>
						<?php endif; ?>
						<?php if($item->othercnt>0) :?>
							<br /><span class="xbnit hasTooltip"  style="margin:0;" title data-original-title="
								<?php echo strip_tags($item->otherlist); ?>
								">
							<?php echo $item->othercnt.' '.Text::_('XBBOOKS_OTHER_ROLES_LISTED'); ?></span>
						<?php endif; ?>
						<br />
						</span>
							<span class="xb09">
								<?php echo $item->pubyear > 0 ? '<span class="xbnit">'.Text::_('XBCULTURE_PUBLISHED').': </span>'.$item->pubyear : ''; ?>						
							</span>
						</p>						
					</td>
					<td>
						<p class="xb095">
							<?php if (!empty($item->summary)) : ?>
									<?php echo $item->summary; ?>
    						<?php else : ?>
    							<span class="xbnit">
    							<?php if (!empty($item->synopsis)) : ?>
    								<?php echo Text::_('XBBOOKS_SYNOPSIS_EXTRACT'); ?>: </span>
    								<?php echo XbcultureHelper::makeSummaryText($item->synopsis,200); ?>
    							<?php else : ?>
    								<?php echo Text::_('XBBOOKS_NO_SUMMARY_SYNOPSIS'); ?></span>
    							<?php endif; ?>
    						<?php endif; ?>
                        </p>
                        <?php if ((!empty($item->synopsis)) && (strlen(strip_tags($item->synopsis))>200)) : ?>
                        	<p class="xbnit xb09">   
                             <?php 
                             	echo Text::_('XBBOOKS_FULLSYNOPSIS').' '.str_word_count(strip_tags($item->synopsis)).' '.Text::_('XBCULTURE_WORDS'); 
                             ?>
							</p>
						<?php endif; ?>
                                    
						<p class="xbnit xb095">
                            <?php if ($item->mencnt>0) : ?>
                                <span class="xbnit hasTooltip"  style="margin:0;" title data-original-title="
								<?php echo strip_tags($item->menlist); ?>
								">
                                <?php echo $item->mencnt.' ';
                                echo ($item->mencnt==1) ? Text::_('XBCULTURE_SUBJECT') : Text::_('XBCULTURE_SUBJECTS');
                                echo ' '.Text::_('XBCULTURE_LISTED'); ?>
                                </span>
                                <?php if (($item->charcnt>0) || ($item->grpcnt>0)) : ?>
                                    <br />
                            	<?php endif; ?>
                            <?php endif; 
                            if ($item->charcnt>0) : ?>
								<span class="xbnit hasTooltip"  style="margin:0;" title data-original-title="
								<?php echo strip_tags($item->charlist); ?>
								">
								<?php echo $item->charcnt.' ';
                                echo ($item->charcnt==1) ? Text::_('XBCULTURE_CHARACTER') : Text::_('XBCULTURE_CHARACTERS');
                                echo ' '.Text::_('XBCULTURE_LISTED'); ?>
                                </span>
                                <?php if ($item->grpcnt>0) : ?>
                                    <br />
                            	<?php endif; ?>
                            <?php endif;  
                            if ($item->grpcnt>0) : ?>
								<span class="xbnit hasTooltip"  style="margin:0;" title data-original-title="
								<?php echo strip_tags($item->grplist); ?>
								">
								<?php echo $item->grpcnt.' ';
                                echo ($item->charcnt==1) ? Text::_('XBCULTURE_GROUP') : Text::_('XBCULTURE_GROUPS');
                                echo ' '.Text::_('XBCULTURE_LISTED'); ?>
                                </span>
							<?php endif; ?>
						</p>
						<?php if($item->ext_links_cnt >0 ) : ?>
							<p class="xbnit xb095">	
								<?php echo Text::_('XBCULTURE_EXTLINK_LBL').': '; 
	                            echo '<span class="xb09 xbnorm">';
	                            echo $item->ext_links_list.'</span>'; ?>
	                    	</p>
						<?php endif; ?>
					</td>
					<td class="hidden-phone">
						<?php if ($item->revcnt==0) : ?>
                            <i><?php echo Text::_('XBBOOKS_NOREVIEW'); ?></i><br /> 
						<?php else: ?>
                        	<?php $stars = (round(($item->averat)*2)/2); ?>
                            <div class="xbbb1">
							<?php if (($this->zero_rating) && ($stars==0)) : ?>
							    <span class="<?php echo $this->zero_class; ?> "></span>
							<?php else : ?>
                                <span style="font-size:10px;">
                                <?php echo str_repeat('<i class="'.$this->star_class.'"></i>',intval($item->averat)); ?>
                                <?php if (($item->averat - floor($item->averat))>0) : ?>
                                    <i class="<?php echo $this->halfstar_class; ?>"></i>
                                    </span> <span style="color:darkgray;">(<?php echo round($item->averat,1); ?>)                                  
                                <?php  endif; ?> 
                                </span> 
                             <?php endif; ?>                        
                            </div>
							<?php foreach ($item->reviews as $rev) : ?>
								<div class="xbbb1">
	                              	<?php if ($item->revcnt>1) : ?>
										<span>
											<?php if (($this->zero_rating) && ($rev->rating==0)) : ?>
												<i class="<?php echo $this->zero_class; ?>"></i>
											<?php else : ?>
										 		<?php echo $rev->rating;?><i class="<?php echo $this->star_class; ?>"></i> 
										 	<?php endif; ?>
										 </span>
	                                <?php endif; ?>
									<a href="<?php echo Route::_($rvlink.$rev->id);?>">
	    								<span class="xbnit"><?php echo Text::_('XBCULTURE_BY').':';?>
	    								<?php if ($rev->reviewer) {
	    								    echo $rev->reviewer;
	    								} else {
	    								    echo Factory::getUser($rev->created_by)->name;
	    								} ?>
	    								</span>
	    								<span class="xb09"> <?php echo HtmlHelper::date($rev->rev_date ,'d M Y'); ?></span>
									</a>
								</div>
							<?php endforeach; ?>
                        <?php endif; ?>
						<div style="margin-top:5px;">
							<a href="<?php echo Route::_($relink.'0&book_id='.$item->id); ?>" 
								class="btn btn-mini btn-success">
								<?php echo Text::_('XBBOOKS_ADDREVIEW'); ?>
							</a>
						</div>
										
					</td>
					<td><?php if($item->first_read) {
						  echo HtmlHelper::date($item->first_read , 'd M Y');                      
                        } ?>
						<br />
						<?php  if(($item->last_read) && ($item->last_read != $item->first_read)) {
						  echo HtmlHelper::date($item->last_read , 'd M Y'); 
                        } ?>
					</td>
					<td>
						<p><a class="label label-success" href="<?php echo $cvlink.$item->catid; ?>" 
							title="<?php echo Text::_( 'XBCULTURE_VIEW_CATEGORY' );?>::<?php echo $item->category_title; ?>">
								<?php echo $item->category_title; ?>
							</a>
							<?php echo ($item->fiction==1) ? ' <span class="label">fiction</span>' : ' <span class="label label-inverse">non-fiction</span>'; ?>
						</p>						
						
						<ul class="inline">
						<?php foreach ($item->tags as $t) : ?>
							<li><a href="<?php echo $tvlink.$t->id; ?>" class="label label-info">
								<?php echo $t->title; ?></a>
							</li>												
						<?php endforeach; ?>
						</ul>						    											
					</td>
					<td class="center hidden-phone">
						<?php echo $item->id; ?>
					</td>
					<td>
						<a href="" data-toggle="modal" data-target="#ajax-pvmodal"
            				 onclick="window.pvid= <?php echo $item->id; ?>;">
            				<i class="icon-eye xbeye"></i>
            			</a>					
					</td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
 		<?php // load the modal for displaying the batch options
        echo HTMLHelper::_('bootstrap.renderModal', 'collapseModal',
            array( 'title' => Text::_('XBCULTURE_BATCH_TITLE'),
                'footer' => $this->loadTemplate('batch_footer')
            ),
            $this->loadTemplate('batch_body')
        ); ?>
	<?php endif; ?>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
<div class="clearfix"></div>
<p><?php echo XbcultureHelper::credit('xbBooks');?></p>
<script>
jQuery(document).ready(function(){
//for preview modal
    jQuery('#ajax-pvmodal').on('show', function () {
        // Load view vith AJAX
        jQuery(this).find('.modal-content').load('index.php?option=com_xbbooks&view=books&layout=modalpv&tmpl=component');
    })
});
</script>
<!-- preview modal window -->
<div class="modal fade xbpvmodal" id="ajax-pvmodal" style="max-width:1200px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Ajax content will be loaded here -->
        </div>
    </div>
</div>


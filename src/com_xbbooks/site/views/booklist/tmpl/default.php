<?php 
/*******
 * @package xbBooks
 * @filesource site/views/booklist/tmpl/default.php
 * @version 0.9.6.a 18th December 2021
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => Text::_('JOPTION_SELECT_TAG')));
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape(strtolower($this->state->get('list.direction')));
if (!$listOrder) {
    $listOrder='cat_date';
    $orderDrn = 'descending';
}
$orderNames = array('title'=>Text::_('XBCULTURE_TITLE'),'pubyear'=>Text::_('COM_XBBOOKS_YEARPUB'), 'averat'=>Text::_('XBCULTURE_AVERAGE_RATING'), 
		'cat_date'=>Text::_('COM_XBBOOKS_DATE_READ'),'category_title'=>Text::_('XBCULTURE_CAPCATEGORY'));

require_once JPATH_COMPONENT.'/helpers/route.php';

$itemid = XbbooksHelperRoute::getCategoriesRoute();
$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
$clink = 'index.php?option=com_xbbooks&view=category'.$itemid.'&id=';

$itemid = XbbooksHelperRoute::getBooksRoute();
$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
$blink = 'index.php?option=com_xbbooks&view=book'.$itemid.'&id=';

$itemid = XbbooksHelperRoute::getReviewsRoute();
$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
$rlink = 'index.php?option=com_xbbooks&view=bookreview'.$itemid.'&id=';

?>
<div class="xbbooks">
	<?php if(($this->header['showheading']) || ($this->header['title'] != '') || ($this->header['text'] != '')) {
		echo XbbooksHelper::sitePageheader($this->header);
	} ?>
	
	<form action="<?php echo JRoute::_('index.php?option=com_xbbooks&view=booklist'); ?>" method="post" name="adminForm" id="adminForm">       
		<?php  // Search tools bar
			if ($this->search_bar) {
				$hide = '';
				if ($this->hide_fict) { $hide .= 'filter_fictionfilt,';}
				if ($this->hide_peep) { $hide .= 'filter_perfilt,filter_prole,';}
				if ($this->hide_char) { $hide .= 'filter_charfilt,';}
				if ((!$this->show_cat) || ($this->hide_cat)) { $hide .= 'filter_category_id,filter_subcats,';}
				if ((!$this->show_tags) || $this->hide_tag) { $hide .= 'filter_tagfilt,filter_taglogic,';}
				echo '<div class="row-fluid"><div class="span12">';
	            echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'hide'=>$hide));       
	         echo '</div></div>';
			} 
		?>
		<div class="row-fluid pagination" style="margin-bottom:10px 0;">
			<div class="pull-right">
				<p class="counter" style="text-align:right;margin-left:10px;">
					<?php echo $this->pagination->getResultsCounter().'.&nbsp;&nbsp;'; 
					   echo $this->pagination->getPagesCounter().'&nbsp;&nbsp;'.$this->pagination->getLimitBox().' per page'; ?>
				</p>
			</div>
			<div>
				<?php  echo $this->pagination->getPagesLinks(); ?>
                <?php echo 'sorted by '.$orderNames[$listOrder].' '.$listDirn ; ?>
			</div>
		</div>
		<div class="row-fluid">
        	<div class="span12">
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-no-items">
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>

	<table class="table table-striped table-hover" style="table-layout:fixed;" id="xbbooklist">	
		<thead>
			<tr>
				<?php if($this->show_pic) : ?>
					<th class="center" style="width:80px">
						<?php echo Text::_( 'COM_XBBOOKS_COVER' ); ?>
					</th>	
                <?php endif; ?>
				<th>
					<?php echo HtmlHelper::_('searchtools.sort','XBCULTURE_TITLE','title',$listDirn,$listOrder).				
    						', '.Text::_('XBCULTURE_AUTHOR').', '.
    						HtmlHelper::_('searchtools.sort','COM_XBBOOKS_PUBYEARCOL','pubyear',$listDirn,$listOrder );				
					?>
				</th>					
                <?php if($this->show_sum) : ?>
				<th class="hidden-phone">
					<?php echo Text::_('XBCULTURE_SUMMARY');?>
				</th>
                <?php endif; ?>
                <?php if ($this->show_rev != 0 ) : ?>
					<th class="xbtc">
						<?php echo HtmlHelper::_('searchtools.sort','Rating','averat',$listDirn,$listOrder); ?>
					</th>
                <?php endif; ?>
				<th>
					<?php echo HtmlHelper::_('searchtools.sort','COM_XBBOOKS_DATE_READ','cat_date',$listDirn,$listOrder ); ?>
				</th>
                <?php if($this->show_ctcol) : ?>
     				<th class="hidden-phone">
    					<?php if ($this->show_cat) {
    					    echo HtmlHelper::_('searchtools.sort','XBCULTURE_CAPCATEGORY','category_title',$listDirn,$listOrder );
    					}
    					if (($this->show_cat) && ($this->show_tags)) {
    						echo ' &amp; ';
    					}
    					if ($this->show_tags) {
    						echo JText::_( 'COM_XBBOOKS_CAPTAGS' );
    					} ?>
    				</th>
               <?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<?php $reviews = ''; ?>
				<tr class="row<?php echo $i % 2; ?>">	
              		<?php if($this->show_pic) : ?>
						<td>
						<?php  $src = trim($item->cover_img);
							if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.$src))) : 
								$src = Uri::root().$src; 
								$tip = '<img src=\''.$src.'\' style=\'max-width:250px;\' />'; 
								?>
								<img class="img-polaroid hasTooltip xbimgthumb" title="" 
									data-original-title="<?php echo $tip; ?>"
									src="<?php echo $src; ?>" border="0" alt="" />							                          
	                    	<?php  endif; ?>	                    
						</td>
                    <?php endif; ?>
					<td>
						<p class="xbtitle">
							<a href="<?php echo JRoute::_(XbbooksHelperRoute::getBookLink($item->id)) ;?>" >
								<b><?php echo $this->escape($item->title); ?></b></a></p> 
						<?php if (!empty($item->subtitle)) :?>
                        	<p><?php echo $this->escape($item->subtitle); ?></p>
                        <?php endif; ?>
						<p>
                        <?php if($item->editcnt>0) : ?>
                           	<?php if ($item->authcnt>0) {
								echo '<span class="hasTooltip" title data-original-title="Authors: '.$item->alist.'">';
                            } else {
                              echo '<span>';
                            } ?>
                       	<span class="xbnit">
                        		<?php echo Text::_($item->editcnt>1 ? 'XBCULTURE_EDITORS' : 'XBCULTURE_EDITOR' ); ?>
                        	</span></span>: 
                        	<?php echo $item->elist; ?>
                        <?php else : ?>
                        	<?php if ($item->authcnt==0) {
                        		echo '<span class="xbnit">'.Text::_('COM_XBBOOKS_NOAUTHOR').'</span>';
                        	} else { ?> 
	                        	<span class="xbnit">
	                        		<?php echo Text::_($item->authcnt>1 ? 'XBCULTURE_CAPAUTHORS' : 'XBCULTURE_AUTHOR' ); ?>
	                        	</span>: 
                        		<?php echo $item->alist; 
                        	} ?>                          	
                        <?php endif; ?>
						<br />
						<span class="xb09">
							<?php if($item->pubyear > 0) {
								echo '<span class="xbnit">'.Text::_('COM_XBBOOKS_CAPPUBLISHED').'</span>: '.$item->pubyear.'<br />'; 
							}?>																		
						</span></p>
					</td>
                    <?php if($this->show_sum) : ?>
					<td class="hidden-phone">
						<p class="xb095">
							<?php if (!empty($item->summary)) : ?>
								<?php echo $item->summary; ?>
    						<?php else : ?>
    							<span class="xbnit">
    							<?php if (!empty($item->synopsis)) : ?>
    								<?php echo Text::_('COM_XBBOOKS_SYNOPSIS_EXTRACT'); ?>: </span>
    								<?php echo XbcultureHelper::makeSummaryText($item->synopsis,250); ?>
    							<?php else : ?>
            						<span class="xbnote">
    								<?php echo Text::_('COM_XBBOOKS_NO_SUMMARY_SYNOPSIS'); ?>
    								</span></span>
    							<?php endif; ?>
    						<?php endif; ?>
                        </p>
                        <?php if (!empty($item->synopsis)) : ?>
                        	<p class="xbnit xb09">   
                             <?php 
                             	echo Text::_('COM_XBBOOKS_CAPSYNOPSIS').' '.str_word_count(strip_tags($item->synopsis)).' '.Text::_('XBCULTURE_WORDS'); 
                             ?>
							</p>
						<?php endif; ?>
					</td>
                	<?php endif; ?>
					<?php if ($this->show_rev != 0 ) : ?>
    					<td>
    						<?php if ($item->revcnt==0) : ?>
    						   <i><?php  echo ($this->show_rev == 1)? Text::_( 'XBCULTURE_NO_RATING' ) : Text::_( 'XBCULTURE_NO_REVIEW' ); ?></i><br />
    						<?php else : ?> 
	                        	<?php $stars = (round(($item->averat)*2)/2); ?>
	                            <div>
								<?php if (($this->zero_rating) && ($stars==0)) : ?>
								    <span class="<?php echo $this->zero_class; ?>"></span>
								<?php else : ?>
	                                <?php echo str_repeat('<i class="'.$this->star_class.'"></i>',intval($item->averat)); ?>
	                                <?php if (($item->averat - floor($item->averat))>0) : ?>
	                                    <i class="<?php echo $this->halfstar_class; ?>"></i>
	                                    <span style="color:darkgray;"> (<?php echo round($item->averat,1); ?>)</span>                                   
	                                <?php  endif; ?> 
	                             <?php endif; ?>                        
	                            </div>
     							<?php if ($this->show_rev == 2) : ?>
                                    <?php foreach ($item->reviews as $rev) : ?>
                                    	<?php $poptip = (empty($rev->summary)) ? 'hasTooltip' : 'hasPopover'; ?> 
										<div class="<?php echo $poptip; ?> xbmb8 xb09"  title 
											data-content="<?php echo htmlentities($rev->summary); ?>"
											data-original-title="<?php echo htmlentities($rev->title); ?>" 
                                		>
    										<?php if ($item->revcnt>1) : ?>
    											<?php echo $rev->rating;?><i class="<?php echo $this->star_class; ?>"></i> 
    			                            <?php endif; ?>
    	                                	<i>by</i> <?php echo $rev->reviewer; ?> 
    	                                	<i>on</i> <?php  echo HtmlHelper::date($rev->rev_date , 'd M Y'); ?>
        								</div>
        							<?php endforeach; ?> 
        						<?php endif; ?>
     						<?php endif; ?>   											
    					</td>
    				<?php endif; ?>
    				<td>
    					<p><?php if($item->lastread=='') {
    						echo '<span class="xbnit">(catalogued)<br />('.HtmlHelper::date($item->cat_date , 'M Y').')</span>';
    					} else {
    						echo HtmlHelper::date($item->lastread , 'd M Y'); 
    					}?> </p>
     				</td>
                    <?php if($this->show_ctcol) : ?>
					<td class="hidden-phone">
     					<?php if($this->show_cats) : ?>												
    						<a class="label label-success" href="<?php echo $clink.$item->catid; ?>"><?php echo $item->category_title; ?></a>
    					<?php endif; ?>
    					<?php echo ($item->fiction==1) ? ' <span class="label">fiction</span>' : ' <span class="label label-inverse">non-fiction</span>'; ?>
    						<?php if($this->show_tags) {
    							$tagLayout = new JLayoutFile('joomla.content.tags');
        						echo '<p>'.$tagLayout->render($item->tags).'</p>';
    						}
        					?>
					</td>
                	<?php endif; ?>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
	<?php echo JHtml::_('form.token'); ?>
      </div>
      </div>
</form>
</div>
<div class="clearfix"></div>
<p><?php echo XbbooksGeneral::credit();?></p>


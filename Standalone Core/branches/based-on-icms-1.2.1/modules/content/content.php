<?php
/**
* Content page
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
* @package		content
* @version		$Id$
 */


function editcontent($contentObj)
{
	global $content_content_handler, $icmsTpl, $xoopsUser;

	if (!$contentObj->isNew()){
		if (!$contentObj->userCanEditAndDelete()) {
			redirect_header($contentObj->getItemLink(true), 3, _NOPERM);
		}
		$contentObj->hideFieldFromForm(array('content_published_date', 'content_updated_date', 'content_uid', 'meta_keywords', 'meta_description', 'short_url', 'content_makesymlink', 'content_css', 'content_visibility', 'content_weight', 'content_status', 'content_cancomment', 'content_showsubs'));
		$sform = $contentObj->getSecureForm(_MD_CONTENT_CONTENT_EDIT, 'addcontent');
		$sform->assign($icmsTpl, 'content_contentform');
		$icmsTpl->assign('content_category_path', $contentObj->getVar('content_title') . ' > ' . _EDIT);
	} else {
		if (!$content_content_handler->userCanSubmit()) {
			redirect_header(CONTENT_URL, 3, _NOPERM);
		}
		$contentObj->setVar('content_uid', $xoopsUser->uid());
		$contentObj->setVar('content_published_date', time());
		$contentObj->hideFieldFromForm(array('content_published_date', 'content_updated_date', 'content_uid', 'meta_keywords', 'meta_description', 'short_url', 'content_makesymlink', 'content_css', 'content_visibility', 'content_weight', 'content_status', 'content_cancomment', 'content_showsubs'));
		$sform = $contentObj->getSecureForm(_MD_CONTENT_CONTENT_SUBMIT, 'addcontent');
		$sform->assign($icmsTpl, 'content_contentform');
		$icmsTpl->assign('content_category_path', _SUBMIT);
	}
}

include_once 'header.php';

$xoopsOption['template_main'] = 'content_content.html';
include_once ICMS_ROOT_PATH . '/header.php';

$content_content_handler = xoops_getModuleHandler('content');

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0 ;
$page = (isset($_GET['page']))?trim(StopXSS($_GET['page'])):((isset($_POST['page']))?trim(StopXSS($_POST['page'])):$clean_content_id);

if (!$page){
	$path = (isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'],0,1) == '/')?substr($_SERVER['PATH_INFO'],1,strlen($_SERVER['PATH_INFO'])):((isset($_SERVER['PATH_INFO']))?$_SERVER['PATH_INFO']:'');
	$path = trim(StopXSS($path));
	$params = explode('/',$path);
	if (count($params) > 0){
		if ($params[0] == 'page'){
			$page = (isset($params[1]))?$params[1]:0;
		}else{
			$page = $params[0];
		}
	}
}

if(!empty($page)){
	$page = (is_int($page)) ? intval($page) : urlencode($page);
	$criteria = $content_content_handler->getContentsCriteria(0,1,false,false,$page,false,'content_id','DESC');
	$content = $content_content_handler->getObjects($criteria);
	$contentObj = false;
	foreach ($content as $content){
		$contentObj = $content;
		break;
	}
	$clean_content_id = $contentObj->getVar('content_id');
}

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';

if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];


/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition 
 */
$valid_op = array ('mod','addcontent','del','');
/** 
 * Only proceed if the supplied operation is a valid operation
 */ 
if (in_array($clean_op,$valid_op,true)){ 
  switch ($clean_op) {
	case "mod":
		$contentObj = $content_content_handler->get($clean_content_id);
  		if ($clean_content_id > 0 && $contentObj->isNew()) {
  			redirect_header(content_getPreviousPage('index.php'), 3, _NOPERM);
		}
		editcontent($contentObj);
		break;

	case "addcontent":
        if (!$xoopsSecurity->check()) {
        	redirect_header(content_getPreviousPage('index.php'), 3, _MD_CONTENT_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
        }
          include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
        $controller = new IcmsPersistableController($content_content_handler);
		$controller->storeFromDefaultForm(_MD_CONTENT_CONTENT_CREATED, _MD_CONTENT_CONTENT_MODIFIED);
		break;

	case "del":
		if (!$contentObj->userCanEditAndDelete()) {
			redirect_header($contentObj->getItemLink(true), 3, _NOPERM);
		}
		if (isset($_POST['confirm'])) {
		    if (!$xoopsSecurity->check()) {
		    	redirect_header($impresscms->urls['previouspage'], 3, _MD_CONTENT_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
		    }
		}
  	    include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
        $controller = new IcmsPersistableController($content_content_handler);
		$controller->handleObjectDeletionFromUserSide();
		$icmsTpl->assign('content_category_path', $content_content_handler->getBreadcrumbForPid($contentObj->getVar('content_id','e'),1) . ' > ' . _DELETE);

		break;

	default:
		if (is_object($contentObj) && $contentObj->accessGranted()) {
			$content_content_handler->updateCounter ( $clean_content_id );
			$content = $contentObj->toArray();
			$icmsTpl->assign('content_content', $content);
			$icmsTpl->assign('showInfo',$contentConfig['show_contentinfo']);
			$showSubs = ($contentConfig['show_relateds'] && $content['content_showsubs'])?true:false;
			$icmsTpl->assign('showSubs',$showSubs);
			if ($contentConfig['show_breadcrumb']){
			    $icmsTpl->assign('content_category_path', $content_content_handler->getBreadcrumbForPid($contentObj->getVar('content_id','e'),1));
			}else{
			    $icmsTpl->assign('content_category_path',false);
			}
		} else {
			redirect_header(CONTENT_URL, 3, _NOPERM);
		}

		if ($contentConfig['com_rule'] && $contentObj->getVar('content_cancomment')) {
			$icmsTpl->assign('content_content_comment', true);
  			include_once ICMS_ROOT_PATH . '/include/comment_view.php';
		}
		break;
}

/**
 * Generating meta information for this page
 */
$icms_metagen = new IcmsMetagen($contentObj->getVar('content_title'), $contentObj->getVar('meta_keywords','n'), $contentObj->getVar('meta_description', 'n'));
$icms_metagen->createMetaTags();

}
$xoTheme->addStylesheet(ICMS_URL.'/modules/content/include/content.css');
$icmsTpl->assign('content_module_home', content_getModuleName(true, true));
include_once 'footer.php';
?>
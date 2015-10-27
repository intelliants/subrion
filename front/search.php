<?php
//##copyright##

$iaSearch = $iaCore->factory('search', iaCore::FRONT);
$iaItem = $iaCore->factory('item');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$itemName = (1 == count($iaCore->requestPath)) ? $iaCore->requestPath[0] : str_replace('search_', '', $iaView->name());

	if (in_array($itemName, $iaItem->getItems()))
	{
		if (!empty($_SERVER['HTTP_REFERER'])) // this makes possible displaying filters block everywhere, but displaying results at the right page
		{
			$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('search_' . $itemName);
			$pageUrl || $pageUrl = IA_URL . 'search/' . $itemName . '/';

			if (parse_url($pageUrl, PHP_URL_PATH) != parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH))
			{
				$pageUrl.= '#' . $iaSearch->httpBuildQuery($_GET);

				$iaView->assign('url', $pageUrl);
				return;
			}
		}

		$iaView->loadSmarty(true);
		$iaView->assign($iaSearch->doAjaxItemSearch($itemName, $_GET));
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$query = empty($_GET['q']) ? null : $_GET['q'];

	$params = $_GET;
	unset($params['page']);

	$iaCore->startHook('phpSearchAfterGetQuery', array('query' => &$query));

	$pagination = array(
		'limit' => 10,
		'start' => 0,
		'total' => 0,
		'url' => IA_SELF . '?q=' . urlencode($query) . '&page={page}'
	);

	$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max($_GET['page'], 1) : 1;
	$pagination['start'] = ($page - 1) * $pagination['limit'];

	$results = null;
	$regular = false;

	if ('search' != $iaView->name() || isset($iaCore->requestPath[0]))
	{
		$itemName = ('search' != $iaView->name())
			? str_replace('search_', '', $iaView->name())
			: $iaCore->requestPath[0];

		if (in_array($itemName, $iaItem->getItems()))
		{
			$results = $iaSearch->doRegularItemSearch($itemName, $params, $pagination['start'], $pagination['limit']);

			$iaView->set('filtersItemName', $itemName);
			$iaView->set('filtersParams', $iaSearch->getParams());

			$iaView->assign('itemName', $itemName);

			$iaView->title($iaSearch->getCaption() ? $iaSearch->getCaption() : $iaView->title());
		}
		else
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}
	}
	else
	{
		$regular = true;
		empty($query) || $results = $iaSearch->doRegularSearch($query, $pagination['start'], $pagination['limit']);
	}

	$iaView->assign('pagination', $pagination);
	$iaView->assign('results', $results);
	$iaView->assign('regular', $regular);
	$iaView->assign('query', $query);

	$iaView->display('search');
}
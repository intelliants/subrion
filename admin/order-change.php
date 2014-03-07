<?php
//##copyright##

if (!isset($_SESSION['manageMode']) && iaCore::ACCESS_FRONT == $iaCore->getAccessType())
{
	return iaView::errorPage(iaView::ERROR_FORBIDDEN);
}

$positions = explode(',', $iaCore->get('block_positions'));

$iaDb->setTable('blocks');

foreach ($positions as $p)
{
	if (isset($_GET[$p . 'Blocks']) && is_array($_GET[$p . 'Blocks']) && $_GET[$p . 'Blocks'])
	{
		foreach ($_GET[$p . 'Blocks'] as $k => $v)
		{
			$blockName = str_replace('start_block_', '', 'start_' . $v);

			$iaCore->startHook('phpOrderChangeBeforeUpdate', array('block' => &$blockName, 'position' => &$p));

			is_numeric($blockName)
				? $iaDb->update(array('id' => $blockName, 'position' => $p, 'order' => $k + 1))
				: $iaDb->update(array('position' => $p, 'order' => $k + 1), "`name` = '" . iaSanitize::sql($blockName) . "'");
		}
	}
}

$iaDb->resetTable();
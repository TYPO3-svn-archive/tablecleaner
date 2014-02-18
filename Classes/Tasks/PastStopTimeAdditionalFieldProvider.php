<?php
/*****************************************************************************
 *  Copyright notice
 *
 *  ⓒ 2013 Michiel Roos <michiel@maxserv.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is free
 *  software; you can redistribute it and/or modify it under the terms of the
 *  GNU General Public License as published by the Free Software Foundation;
 *  either version 2 of the License, or (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful, but
 *  WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 *  or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 *  more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ****************************************************************************/

/**
 * Additional field provider for the PastStopTime scheduler task
 *
 * @package TYPO3
 * @subpackage tablecleaner
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php
 *    GNU Public License, version 2
 */
class tx_tablecleaner_tasks_PastStopTimeAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Render additional information fields within the scheduler backend.
	 *
	 * @param  array $taskInfo
	 * @param  tx_tablecleaner_tasks_PastStopTime $task : task object
	 * @param  tx_scheduler_Module $schedulerModule : reference to the calling
	 *    object (BE module of the Scheduler)
	 *
	 * @internal  param array $taksInfo : array information of task to return
	 * @return  array      additional fields
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields(
	 *    $taskInfo, $task, $schedulerModule
	 * )
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();

		$tables = Tx_Tablecleaner_Utility_Base::getTablesWithHiddenAndEndtime();
		// tables
		if (empty($taskInfo['pastStopTimeTables'])) {
			$taskInfo['pastStopTimeTables'] = array();
			// In case of editing the task, set to currently selected value
			if ($schedulerModule->CMD === 'edit') {
				$taskInfo['pastStopTimeTables'] = $task->getTables();
			}
		}

		$fieldName = 'tx_scheduler[pastStopTimeTables][]';
		$fieldId = 'task_pastStopTimeTables';
		$fieldOptions = $this->getTableOptions($tables, $taskInfo['pastStopTimeTables']);
		$fieldHtml =
			'<select name="' . $fieldName . '" id="' . $fieldId . '" class="wide" size="10" multiple="multiple">' .
			$fieldOptions .
			'</select>';

		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:tablecleaner/Resources/Private/Language/locallang.xml:tasks.general.tables',
			'cshKey' => 'tablecleaner',
			'cshLabel' => $fieldId,
		);

		// day limit
		if (empty($taskInfo['pastStopTimeDayLimit'])) {
			if ($schedulerModule->CMD === 'add') {
				$taskInfo['pastStopTimeDayLimit'] = '31';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['pastStopTimeDayLimit'] = $task->getDayLimit();
			} else {
				$taskInfo['pastStopTimeDayLimit'] = $task->getDayLimit();
			}
		}

		$fieldId = 'task_dayLimit';
		$fieldCode = '<input type="text" name="tx_scheduler[pastStopTimeDayLimit]" id="' .
			$fieldId . '" value="' . htmlspecialchars($taskInfo['pastStopTimeDayLimit']) . '" size="4"/>';
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:tablecleaner/Resources/Private/Language/locallang.xml:tasks.pastStopTime.dayLimit',
			'cshKey' => 'tablecleaner',
			'cshLabel' => $fieldId,
		);

		return $additionalFields;
	}

	/**
	 * Build select options of available tables and set currently selected tables
	 *
	 * @param  array $tables all tables
	 * @param  array $selectedTables Selected tables
	 *
	 * @return string HTML of selectbox options
	 */
	protected function getTableOptions(array $tables, array $selectedTables) {
		$options = array();

		foreach ($tables as $tableName) {
			if (in_array($tableName, $selectedTables)) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options[] =
				'<option value="' . $tableName . '"' . $selected . '>' .
				$tableName .
				'</option>';
		}

		return implode('', $options);
	}

	/**
	 * This method checks any additional data that is relevant to the specific task.
	 * If the task class is not relevant, the method is expected to return TRUE.
	 *
	 * @param   array $submittedData : reference to the array containing the
	 *    data submitted by the user
	 * @param \tx_scheduler_Module $schedulerModule :
	 *    reference to the calling object (BE module of the Scheduler)
	 *
	 * @return   boolean      True if validation was ok (or selected class is
	 *    not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$isValid = TRUE;

		if (is_array($submittedData['pastStopTimeTables'])) {
			$tables = Tx_Tablecleaner_Utility_Base::getTablesWithHiddenAndEndtime();
			foreach ($submittedData['pastStopTimeTables'] as $table) {
				if (!in_array($table, $tables)) {
					$isValid = FALSE;
					$schedulerModule->addMessage(
						$GLOBALS['LANG']->sL(
							'LLL:EXT:tablecleaner/Resources/Private/Language/locallang.xml:tasks.general.invalidTables'
						),
						t3lib_FlashMessage::ERROR
					);
				}
			}
		} else {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL(
					'LLL:EXT:tablecleaner/Resources/Private/Language/locallang.xml:tasks.general.noTables'
				),
				t3lib_FlashMessage::ERROR
			);
		}

		if ($submittedData['pastStopTimeDayLimit'] <= 0) {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL(
					'LLL:EXT:tablecleaner/Resources/Private/Language/locallang.xml:tasks.general.invalidNumberOfDays'
				),
				t3lib_FlashMessage::ERROR
			);
		}

		return $isValid;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches.
	 *
	 * @param   array $submittedData : array containing the data submitted by
	 *    the user
	 * @param   tx_scheduler_Task $task : reference to the current task object
	 *
	 * @return   void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		/** @var $task tx_tablecleaner_tasks_PastStopTime */
		$task->setDayLimit((int)$submittedData['pastStopTimeDayLimit']);
		$task->setTables($submittedData['pastStopTimeTables']);
	}
}

if (defined('TYPO3_MODE') &&
	isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tablecleaner/Classes/Tasks/PastStopTimeAdditionalFieldProvider.php'])
) {
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tablecleaner/Classes/Tasks/PastStopTimeAdditionalFieldProvider.php']);
}
?>
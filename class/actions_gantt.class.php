<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_gantt.class.php
 * \ingroup gantt
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsGantt
 */
class ActionsGantt
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */

	function getCalendarEvents($parameters, &$object, &$action, $hookmanager)
	{
	    $TContext = explode(':', $parameters['context']);

	    if (in_array('agenda', $TContext) || in_array('projectcard', $TContext))
	    {
            global $conf,$db;

            if(!empty($conf->global->GANTT_SHOW_TASK_INTO_CALENDAR_VIEW)) {

                $month = GETPOST('month');
                $year = GETPOST('year');

                if(empty($month)) {
                    $time = time();
                }
                else {
                    $time = strtotime($year.'-'.$month.'-01');
                }

                $start = date('Y-m-01',$time);
                $end = date('Y-m-t',$time);

                $fk_user = (int)GETPOST('filtert');
                $fk_project = (int)GETPOST('projectid');
                dol_include_once('/gantt/class/gantttask.class.php');
                dol_include_once('/gantt/class/gantt.class.php');
                $TTaskObject = GanttPatern::getTasks($start, $end, $fk_project,0,'','',$fk_user);

                if(!empty($TTaskObject)) {

                    foreach($TTaskObject as $task) {

                        if($task->date_end<$task->date_start)$task->date_end = $task->date_start;

                        $task->userassigned=array();
                        $TContact = $task->getListContactId();
                        if(!empty($TContact)) {
                            foreach($TContact as $fk_contact) {
                                $task->userassigned[$fk_contact] = array('id'=>$fk_contact);
                            }


                        }

                        $gantttask = unserialize(strtr(serialize($task),array('O:4:"Task"'=>'O:9:"GanttTask"'))); //hop hop y a un lapin dans le chapeau

                        $daycursor=$gantttask->date_start;


                        while($daycursor<=$task->date_end) {

                            $annee = date('Y',$daycursor);
                            $mois = date('m',$daycursor);
                            $jour = date('d',$daycursor);
                            $daykey=dol_mktime(0,0,0,$mois,$jour,$annee);


                            $this->results['eventarray'][$daykey][] = $gantttask;

                            $daycursor=strtotime('+1day',$daycursor);
                        }


                    }

                    return 1;

                }


            }



	    }

	}

	function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{

		$TContext = explode(':', $parameters['context']);
		if (in_array('projecttaskcard', $TContext) || in_array('projectcard', $TContext))
		{
			if($action === 'edit') {
				?>
				<script type="text/javascript">
				$(document).ready(function() {
					$input = $('input[name=options_color]');
					if($input.val() == '') $input.val('#ffffff');
					$input.attr('type','color');
				});
				</script>

				<?php

			}


		}

		if (in_array('projecttaskcard', $TContext)) {
		    global $langs;

		    ?>
				<script type="text/javascript">
				$(document).ready(function() {
					$('div.tabsAction').first().append('<a class="butAction" href="?id=<?php echo $object->id ?>&action=gantt-move-all-task"><?php echo $langs->trans('MoveAllTasks') ?></a>');
				});
				</script>

		    <?php
		}
	}


	function doActions($parameters, &$object, &$action, $hookmanager)
	{

	    $TContext = explode(':', $parameters['context']);
	    global $conf,$user;

	    if (in_array('projecttaskcard', $TContext)) {
	        if(!empty($conf->global->GANTT_SHOW_TASK_INTO_CALENDAR_VIEW)) {

    	        if (GETPOST('actionmove','alpha') == 'mupdate')
    	        {
    	            list($dummy, $newday) = explode('_',GETPOST('newdate'));

    	            $object->fetch(GETPOST('id'));

    	            $newtime=strtotime($newday);

    	            $diff = $newtime - $object->date_start;

    	            if(abs($diff)>86399) {
    	                $object->date_start+=$diff;
    	                $object->date_end+=$diff;
    	                $object->update($user);
    	            }

    	            $backtopage=GETPOST('backtopage','alpha');
    	            if (! empty($backtopage))
    	            {
    	                header("Location: ".$backtopage);
    	                exit;
    	            }
    	        }

	        }

	        if($action == 'gantt-move-all-task') {
    		    global $langs, $user;

    		    $db = &$object->db;

    		    $task=new Task($db);
    		    $tasks = $task->getTasksArray(null, null, $object->id);

    		    $tasksid=array();
    		    foreach($tasks as &$t) {
    		        if($t->progress<100 && $t->planned_workload>0) $tasksid[] = $t->id;
    		    }
    		    if(!empty($tasksid)) {

        		    define('INC_FROM_DOLIBARR',true);
        		    dol_include_once('/gantt/config.php');
        		    dol_include_once('/gantt/class/gantt.class.php');

        		    $t_start = strtotime('midnight +1day');
        		    $t_end = strtotime('+6 month');

        		    $Tab = GanttPatern::get_better($tasksid, $t_start, $t_end);

        		    foreach($Tab as $fk_task=>$pattern) {

        		          $task=new Task($db);
        		          $task->fetch($fk_task);
        		          $task->date_start = $pattern['start'];
        		          $task->date_end = strtotime('+'.$pattern['duration'].'day -1day', $task->date_start) + 86399;
                          $task->update($user);

        		    }
    		    }
	        }
		}
	}
}
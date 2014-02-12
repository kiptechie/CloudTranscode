<?php

// This class serves as a skeletton for classes impleting actual activity
class BasicActivity
{
	private $activityType; // Type of activity
	private $activityResult; // Contain activity result output

	function __construct($params)
	{
		if (!isset($params["name"]) || !$params["name"])
			throw new Exception("Can't instantiate asicActivity: 'name' is not provided or empty !\n");

		if (!isset($params["version"]) || !$params["version"])
			throw new Exception("Can't instantiate BasicActivity: 'version' is not provided or empty !\n");

		if (!$this->init_activity($params))
			throw new Exception("Unable to init the activity !\n");
	}

	private function init_activity($params)
	{
		global $swf;

		// Save activity info
		$this->activityType = array(
			"name"    => $params["name"],
			"version" => $params["version"]);

		// Check if activity already exists 
		try {
			$swf->describeActivityType(array(
				"domain"       => $params["domain"],
				"activityType" => $this->activityType
				));
			return true;
		} catch (\Aws\Swf\Exception\UnknownResourceException $e) {
			echo "Activity '" . $params["name"] . "' doesn't exists. Creating it ...\n";
		} catch (Exception $e) {
			echo "Unable describe activity ! " . $e->getMessage() . "\n";
			return false;
		}

		// Register if doesn't exists
		print_r($params);
		try {
			$swf->registerActivityType($params);
		} catch (Exception $e) {
			echo "Unable to register new activity ! " . $e->getMessage() . "\n";
			return false;
		}

		return true;
	}

	// Perform the activity
	protected function do_activity($task)
	{
		// To be implemented in class that extends this class
	}

    // Send activity failed to SWF
	public function activity_failed($task, $reason = "", $details = "")
	{
		global $swf;

		try {
			log_out("ERROR", basename(__FILE__), "[$reason] $details");
			$swf->respondActivityTaskFailed(array(
				"taskToken" => $task["taskToken"],
				"reason"    => $reason,
				"details"   => $details,
				));
		} catch (Exception $e) {
			log_out("ERROR", basename(__FILE__), "Unable to send 'Task Failed' response ! " . $e->getMessage());
			return false;
		}
	}

    // Send activity completed to SWF
	public function activity_completed($task, $result)
	{
		global $swf;
		
		try {
            log_out("INFO", basename(__FILE__),"Notify SWF that activity is completed !");
			$swf->respondActivityTaskCompleted(array(
				"taskToken" => $task["taskToken"],
				"result"    => json_encode($result),
				));
		} catch (Exception $e) {
			log_out("ERROR", basename(__FILE__), "Unable to send 'Task Completed' response ! " . $e->getMessage());
			return false;
		}
	}
}


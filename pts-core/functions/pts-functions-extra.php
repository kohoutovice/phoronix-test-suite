<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-extra.php: "Extra" functions needed for some operations.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function pts_remove_saved_result($identifier)
{
	$return_value = false;

	if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
	{
		@unlink(SAVE_RESULTS_DIR . $identifier . "/composite.xml");

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/result-graphs/*.png") as $remove_file)
			@unlink($remove_file);

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/test-*.xml") as $remove_file)
			@unlink($remove_file);

		@unlink(SAVE_RESULTS_DIR . $identifier . "/pts-results-viewer.xsl");
		@rmdir(SAVE_RESULTS_DIR . $identifier . "/result-graphs/");
		@rmdir(SAVE_RESULTS_DIR . $identifier);
		echo "Removed: $identifier\n";
		$return_value = true;
	}
	return $return_value;
}
function pts_tests_in_suite($object)
{
	$type = pts_test_type($object);
	$tests = array();

	if($type == "TEST_SUITE")
	{
		$xml_parser = new tandem_XmlReader(@file_get_contents(XML_SUITE_DIR . $object . ".xml"));
		$suite_benchmarks = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		foreach($suite_benchmarks as $benchmark)
			foreach(pts_tests_in_suite($benchmark) as $sub_test)
				array_push($tests, $sub_test);
	}
	else if($type == "TEST")
		return array($object);

	return array_unique($tests);
}
function pts_print_format_tests($object, $steps = -1)
{
	$steps++;
	if(pts_test_type($object) == "TEST_SUITE")
	{
		$xml_parser = new tandem_XmlReader(@file_get_contents(XML_SUITE_DIR . $object . ".xml"));
		$suite_benchmarks = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		if($steps > 0)
			asort($suite_benchmarks);

		if($steps == 0)
			echo $object . "\n";
		else
			echo str_repeat("  ", $steps) . "+ " . $object . "\n";

		foreach($suite_benchmarks as $benchmark)
		{
			echo pts_print_format_tests($benchmark, $steps);
		}
	}
	else
		echo str_repeat("  ", $steps) . "* " . $object . "\n";
}
function pts_generate_download_cache()
{
	if(!is_dir(PTS_DOWNLOAD_CACHE_DIR))
		mkdir(PTS_DOWNLOAD_CACHE_DIR);
	else
	{
		if(is_file(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto"))
			unlink(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto");
	}

	$xml_writer = new tandem_XmlWriter();
	$xml_writer->addXmlObject(P_CACHE_PTS_VERSION, -1, PTS_VERSION);
	$file_counter = 0;
	foreach(glob(TEST_RESOURCE_DIR . "*/downloads.xml") as $downloads_file)
	{
		$test = substr($downloads_file, strlen(TEST_RESOURCE_DIR), 0 - 14);
		$xml_parser = new tandem_XmlReader($downloads_file);
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$download_to = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_DESTINATION);
		$cached = false;

		echo "\nChecking Downloads For: " . $test . "\n";
		$test_install_message = true;

		for($i = 0; $i < count($package_url); $i++)
		{
			if(empty($package_filename[$i]))
				$package_filename[$i] = basename($package_url[$i]);

			if(is_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) && (empty($package_md5[$i]) || md5_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) == $package_md5[$i]))
			{
				echo "\tPreviously Cached: " . $package_filename[$i] . "\n";
				$cached = true;
			}
			else
			{
				if(is_dir(TEST_ENV_DIR . $test . "/"))
				{
					if(is_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]) && $download_to[$i] != "SHARED")
					{
						if(empty($package_md5[$i]) || md5_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]) == $package_md5[$i])
						{
							echo "\tCaching: " . $package_filename[$i] . "\n";

							if(copy(TEST_ENV_DIR . $test . "/" . $package_filename[$i], PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]))
								$cached = true;
						}
					}
					else if(is_file(TEST_ENV_DIR . "pts-shared/" . $package_filename[$i]) && $download_to[$i] == "SHARED")
					{
						if(empty($package_md5[$i]) || md5_file(TEST_ENV_DIR . "pts-shared/" . $package_filename[$i]) == $package_md5[$i])
						{
							echo "\tCaching: " . $package_filename[$i] . "\n";

							if(copy(TEST_ENV_DIR . "pts-shared/" . $package_filename[$i], PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]))
								$cached = true;
						}
					}
				}
				else
				{
					if($test_install_message)
					{
						echo "\tTest Not Installed\n";
						$test_install_message = false;
					}
				}
			}

			if($cached)
			{
				$xml_writer->addXmlObject(P_CACHE_PACKAGE_FILENAME, $file_counter, $package_filename[$i]);
				$xml_writer->addXmlObject(P_CACHE_PACKAGE_MD5, $file_counter, $package_md5[$i]);
				$file_counter++;
			}
		}
	}

	$cache_xml = $xml_writer->getXML();
	file_put_contents(PTS_DOWNLOAD_CACHE_DIR . "pts-download-cache.xml", $cache_xml);
}
function pts_dependency_name($dependency)
{
	$return_title = "";
	if(is_file(XML_DISTRO_DIR . "generic-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		for($i = 0; $i < count($title) && empty($return_title); $i++)
		{
			if($dependency == $package_name[$i])
			{
				$return_title = $title[$i];
			}
		}
	}

	return $return_title;
}
function pts_estimated_time_string($time)
{
	$strlen_time = strlen($time);

	if(strlen($time_trim = str_replace("~", "", $time)) != $strlen_time)
		$formatted_string = "Approximately " . $time_trim;
	else if(strlen($time_trim = str_replace(array('l'), '', $time)) != $strlen_time)
		$formatted_string = "Less Than " . $time_trim;
	else if(strlen($time_trim = str_replace(array('g'), '', $time)) != $strlen_time)
		$formatted_string = "Greater Than " . $time_trim;
	else if(strlen($time_trim = str_replace("-", ", ", $time)) != $strlen_time)
	{
		$time_trim = explode(",", $time_trim);

		for($i = 0; $i < count($time_trim); $i++)
			$time_trim[$i] = trim($time_trim[$i]);

		if(count($time_trim) == 2)
			$formatted_string = $time_trim[0] . " to " . $time_trim[1];
	}
	else
		$formatted_string = $time;

	$formatted_string .= " Minutes";

	return $formatted_string;
}
?>

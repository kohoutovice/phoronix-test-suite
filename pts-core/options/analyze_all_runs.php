<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class analyze_all_runs
{
	public static function run($r)
	{
		$identifier = $r[0];

		if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
		{
			$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $identifier . "/composite.xml");

			pts_set_assignment("GRAPH_RENDER_TYPE", "CANDLESTICK");
			if(pts_save_result($identifier . "/composite.xml", $composite_xml))
			{
				echo "\n" . $identifier . " has been re-rendered to show all test runs.\n";
				pts_display_web_browser(SAVE_RESULTS_DIR . $identifier . "/composite.xml");
			}
		}
		else
		{
			echo pts_string_header($identifier . " was not found.");
		}
	}
}

?>

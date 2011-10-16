Aurora Online Judge

Setup:
	Copy all files into a directory named "aurora" inside your Apache's Document Root.
	Open (create, if required) the file "<path-to-document-root>/aurora/system_config.php" and set the variables (with appropriate values) as shown below:
		<?php
		$mysql_hostname = "127.0.0.1";
		$mysql_username = "kaustubh";
		$mysql_password = "password";
		$mysql_database = "aurora";
		$admin_teamname = "Judge";
		$admin_password = "password";
		?>
	Using a browser, open "https://hostname/aurora/?display=doc" to read further instructions on how to use this software.
	To judge sumissions, run the script "aurora.py" on (preferably) a virtual machine that satisfies the server configuration specified in the FAQ.

Created by
	Kaustubh Karkare
	kaustubh.karkare@gmail.com
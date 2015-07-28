# Raft-php
Implementation of Raft Consensus Algorithm in php

Run basic test cases by naviagating to the download directory and running command

php TestCases.php [port]

Although node closing has been implemented but due to unreliability of php sockets, sometimes the closing fails and new connections cannot be made on that port. Therefore you should always change port by increments of 50 or more each time the tests are run.

Due to this unreliabilty you may also observe that the test gets stuck or keeps sending the same data with no reply which means that some port failed to get closed and is thus not allowing a reconnection, in that case also you should exit the console and try again with port incremented by 50.

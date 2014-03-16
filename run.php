<?php

/**
 * Created by Bastian Bringenberg <mail@bastian-bringenberg.de>
 * Usage and more informations available in ReadMe.md
 * Origin: https://github.com/Avalarion/wbblite2_to_mybb
 * Missing and / or not planned:
 * + Userfields
 * + Groups
 * + Avatars
 */


if(!file_exists('./config.php')) {
	die('config.php missing. Please read ReadMe.md first!');
}
require_once('./config.php');

/**
 * WBBLite2Exporter_MyBBImporter
 *
 * This Class is doing all the work for us =).
 */
class WBBLite2Exporter_MyBBImporter {

	/**
	 * @var DbConnection $wbbDb;
	 */
	protected $wbbDb = null;

	/**
	 * @var DbConnection $mybbDb;
	 */
	protected $mybbDb = null;

	/**
	 * @var boolean $verbose;
	 */
	protected $verbose = true;

	/**
	 * function run
	 *
	 * @return int final programm state
	 */
	public function run() {
		try {
			$this->generateDbConnections();
			$this->copyUsers();
			$this->copyBoards();
			$this->copyThreads();
			$this->copyPosts();
			$this->copyPrivateMessages();
		}catch(Exception $e) {
			$this->printLine($e->getMessage());
			return 1;
		}
		return 0;
	} 

	/**
	 * function generateDbConnections
	 *
	 * @throws Exception if connection to one or more dbs not possible
	 * @return void
	 */
	protected function generateDbConnections() {
		$this->printLine('Connection to DBs...');
		$this->wbbDb = new mysqli($GLOBALS['wbb']['dbhost'], $GLOBALS['wbb']['dbuser'], $GLOBALS['wbb']['dbpass'], $GLOBALS['wbb']['db'], $GLOBALS['wbb']['dbport']);
		if ($this->wbbDb->connect_error)
			throw new Exception('Connect Error (' . $this->wbbDb->connect_errno . ') ' . $this->wbbDb->connect_error);
		$this->mybbDb = new mysqli($GLOBALS['mybb']['dbhost'], $GLOBALS['mybb']['dbuser'], $GLOBALS['mybb']['dbpass'], $GLOBALS['mybb']['db'], $GLOBALS['mybb']['dbport']);
		if ($this->mybbDb->connect_error)
			throw new Exception('Connect Error (' . $this->mybbDb->connect_errno . ') ' . $this->mybbDb->connect_error);

		$this->printLine('+ successfull');
	}

	/**
	 * function copyUsers
	 * Copys all the Users from WBB Database to MyBB Database
	 *
	 * @return void
	 */
	protected function copyUsers() {
		$this->printLine('Copy Users');
		$this->printLine('+ Fetching WbbUsers...');
		$users = $this->getWbbUsers();
		$this->printLine('+ Got ' . count($users) . ' Users');
		$this->printLine('+ Will now truncate users from MyBB and import WBB Ones');
		$this->setMyBbUsers($users);
		$this->printLine('+ UserImport Done.');

	}

	/**
	 * function getWbbUsers
	 * Collect all required informations from 
	 *
	 * @return array Users in 2 dimensional array
	 */
	protected function getWbbUsers() {
		$users = $this->wbbDb->query('SELECT * FROM wcf' . $GLOBALS['wbb']['id'] . '_user;');
		$usersArray = array();
		while(($tmp = $users->fetch_assoc()) != null)
			$usersArray[] = $tmp;
		return $usersArray;
	}

	/**
	 * function setMyBbUsers
	 * Removing Content from MyBBs UserTable
	 * Setting WbbUsers as new MyBBUsers
	 * 
	 * @param array $users 2 dimensional Users Array
	 * @throws Exception if Query Fails.
	 * @return void
	 */
	protected function setMyBbUsers($users) {
		$adminUsers = explode(',', $GLOBALS['mybb']['adminUser']);
		$this->mybbDb->query('TRUNCATE mybb_users;');
		foreach($users as $user) {
			$this->printVerboseLine('++ User: ' . $user['username']);
			$isAdmin = in_array($user['userID'], $adminUsers) ? '4' : '0';
			$query = 'INSERT INTO mybb_users (uid, username, salt, password, email, usertitle, usergroup, regdate, lastactive, lastvisit, lastpost, signature, 
				allownotices, hideemail, subscriptionmethod, invisible, receivepms,receivefrombuddy, pmnotice, pmnotify, showsigs, showavatars, 
				showquickreply, showredirect, showcodebuttons, usernotes) 
			VAlUES(
				"' . $this->mybbDb->real_escape_string($user['userID']) . '", 
				"' . $this->mybbDb->real_escape_string($user['username']) . '", 
				"' . $this->mybbDb->real_escape_string($user['salt']) . '", 
				"' . $this->mybbDb->real_escape_string($user['password']) . '", 
				"' . $this->mybbDb->real_escape_string($user['email']) . '", 
				"' . $this->mybbDb->real_escape_string($user['userTitle']) . '", 
				"' . $isAdmin . '"
				"' . $this->mybbDb->real_escape_string($user['registrationDate']) . '", 
				"' . $this->mybbDb->real_escape_string($user['lastActivityTime']) . '", 
				"' . $this->mybbDb->real_escape_string($user['lastActivityTime']) . '", 
				"' . $this->mybbDb->real_escape_string($user['lastActivityTime']) . '", 
				"' . $this->mybbDb->real_escape_string($user['signature']) . '",
				1, 
				0,
				0,
				0,
				1,
				0,
				1,
				1,
				1,
				1,
				1,
				1,
				1,
				1,
				1
			);';
			if(!$this->mybbDb->query($query))
				throw new Exception('User Query failed: ' . $this->mybbDb->error . PHP_EOL . $query);
		}
	}

	/**
	 * function copyBoards
	 * copies boards from Wbb to myBB
	 *
	 * @return void
	 */
	protected function copyBoards() {
		$this->printLine('Copy Boards');
		$this->printLine('+ Fetching WBB Boards...');
		$boards = $this->getWbbBoards();
		$this->printLine('+ Got ' . count($boards) . ' Boards');
		$this->printLine('+ Will now truncate Boards from MyBB and import WBB Ones');
		$this->setMyBbBoards($boards);
		$this->printLine('+ BoardImport Done.');
	}

	/**
	 * function getWbbBoards
	 *
	 * @return array
	 */
	protected function getWbbBoards() {
		$boards = $this->wbbDb->query('SELECT * FROM wbb1_' . $GLOBALS['wbb']['id'] . '_board;');
		$boardsArray = array();
		while(($tmp = $boards->fetch_assoc()) != null)
			$boardsArray[] = $tmp;
		return $boardsArray;
	}

	/**
	 * function setMyBbBoards
	 * Removing Content from MyBBs BoardTable
	 * Setting WbbBoard as new MyBBBoards
	 *
	 * @param array $boards
	 * @throws Exception if Query fails
	 * @return void
	 */
	protected function setMyBbBoards($boards) {
		$this->mybbDb->query('TRUNCATE mybb_forums;');
		foreach($boards as $board) {
			$boardType = ($board['boardType'] === 1) ? 'c' : 'f';
			$this->printVerboseLine('++ Board: ' . $board['title']);
			$query = 'INSERT INTO mybb_forums (fid, name, description, pid, active, open, type) 
			VAlUES(
				"' . $this->mybbDb->real_escape_string($board['boardID']) . '", 
				"' . $this->mybbDb->real_escape_string($board['title']) . '", 
				"' . $this->mybbDb->real_escape_string($board['description']) . '", 
				"' . $this->mybbDb->real_escape_string($board['parentID']) . '",
				1,
				1,
				"' . $boardType . '"
			);';
			if(!$this->mybbDb->query($query))
				throw new Exception('Board Query failed: ' . $this->mybbDb->error);
		}
	}

	/**
	 * function copyThreads
	 * copies Thread from Wbb to myBB
	 *
	 * @return void
	 */
	protected function copyThreads() {
		$this->printLine('Copy Threads');
		$this->printLine('+ Fetching WBB Threads...');
		$threads = $this->getWbbThreads();
		$this->printLine('+ Got ' . count($threads) . ' Threads');
		$this->printLine('+ Will now truncate Threads from MyBB and import WBB Ones');
		$this->setMyBbThreads($threads);
		$this->printLine('+ ThreadsImport Done.');
	}

	/**
	 * function getWbbThreads
	 *
	 * @return array
	 */
	protected function getWbbThreads() {
		$threads = $this->wbbDb->query('SELECT * FROM wbb1_' . $GLOBALS['wbb']['id'] . '_thread;');
		$threadsArray = array();
		while(($tmp = $threads->fetch_assoc()) != null)
			$threadsArray[] = $tmp;
		return $threadsArray;
	}

	/**
	 * function setMyBbThreads
	 *
	 * @param array $threads
	 * @return void
	 */
	protected function setMyBbThreads($threads) {
		$this->mybbDb->query('TRUNCATE mybb_threads;');
		foreach($threads as $thread) {
			$this->printVerboseLine('++ Threads: ' . $thread['topic']);
			$query = 'INSERT INTO mybb_threads (tid, subject, fid, visible) 
			VAlUES(
				"' . $this->mybbDb->real_escape_string($thread['threadID']) . '", 
				"' . $this->mybbDb->real_escape_string($thread['topic']) . '", 
				"' . $this->mybbDb->real_escape_string($thread['boardID']) . '",
				1
			);';
			if(!$this->mybbDb->query($query))
				throw new Exception('Thread Query failed: ' . $this->mybbDb->error);
		}
	}

	/**
	 * function copyPost
	 * copies Posts from Wbb to myBB
	 *
	 * @return void
	 */
	protected function copyPosts() {
		$this->printLine('Copy Posts');
		$this->printLine('+ Fetching WBB Posts...');
		$posts = $this->getWbbPosts();
		$this->printLine('+ Got ' . count($posts) . ' Posts');
		$this->printLine('+ Will now truncate Posts from MyBB and import WBB Ones');
		$this->setMyBbPosts($posts);
		$this->printLine('+ PostsImport Done.');
	}

	/**
	 * function getWbbPosts
	 * 
	 * @return array
	 */
	protected function getWbbPosts() {
		$posts = $this->wbbDb->query('SELECT * FROM wbb1_' . $GLOBALS['wbb']['id'] . '_post;');
		$postsArray = array();
		while(($tmp = $posts->fetch_assoc()) != null)
			$postsArray[] = $tmp;
		return $postsArray;
	}

	/**
	 * function setMyBbPosts
	 *
	 * @param array $posts
	 * @return void
	 */
	protected function setMyBbPosts($posts) {
		$this->mybbDb->query('TRUNCATE mybb_posts;');
		foreach($posts as $post) {
			$this->printVerboseLine('++ Posts: ' . $post['subject']);
			$query = 'INSERT INTO mybb_posts (pid, tid, replyto, fid, subject, uid, username, dateline, message, visible, includesig) 
			VAlUES(
				"' . $this->mybbDb->real_escape_string($post['postID']) . '", 
				"' . $this->mybbDb->real_escape_string($post['threadID']) . '", 
				"' . $this->mybbDb->real_escape_string($post['parentPostID']) . '", 
				"0", ' . '' /** ForumID??? */ . '
				"' . $this->mybbDb->real_escape_string($post['subject']) . '", 
				"' . $this->mybbDb->real_escape_string($post['userID']) . '", 
				"' . $this->mybbDb->real_escape_string($post['username']) . '", 
				"' . $this->mybbDb->real_escape_string($post['time']) . '", 
				"' . $this->mybbDb->real_escape_string($post['message']) . '",
				1,
				1
			);';
			if(!$this->mybbDb->query($query))
				throw new Exception('Post Query failed: ' . $this->mybbDb->error);
		}
	}

	/**
	 * function copyPrivateMessages
	 * copies PrivateMessages from Wbb to myBB
	 *
	 * @return void
	 */
	protected function copyPrivateMessages() {
		$this->printLine('Copy PrivateMessages');
		$this->printLine('+ Fetching WBB PrivateMessages...');
		$privateMessages = $this->getWbbPrivateMessages();
		$this->printLine('+ Got ' . count($privateMessages) . ' PrivateMessages');
		$this->printLine('+ Will now truncate PrivateMessages from MyBB and import WBB Ones');
		$this->setMyBbPrivateMessages($privateMessages);
		$this->printLine('+ PrivateMessagesImport Done.');
	}

	/**
	 * function getWbbPrivateMessages
	 * 
	 * @return array
	 */
	protected function getWbbPrivateMessages() {
		$query = 'SELECT wcf' . $GLOBALS['wbb']['id'] . '_pm.*, GROUP_CONCAT(wcf' . $GLOBALS['wbb']['id'] . '_pm_to_user.recipientID SEPARATOR ", ") as target FROM wcf' . $GLOBALS['wbb']['id'] . '_pm JOIN wcf' . $GLOBALS['wbb']['id'] . '_pm_to_user ON wcf' . $GLOBALS['wbb']['id'] . '_pm.pmID = wcf' . $GLOBALS['wbb']['id'] . '_pm_to_user.pmID GROUP BY wcf' . $GLOBALS['wbb']['id'] . '_pm_to_user.pmID;';
		$privateMessages = $this->wbbDb->query($query);
		$privateMessagesArray = array();
		while(($tmp = $privateMessages->fetch_assoc()) != null)
			$privateMessagesArray[] = $tmp;
		return $privateMessagesArray;
	}

	/**
	 * function setMyBbPrivateMessages
	 *
	 * @param array $privateMessages
	 * @return void
	 */
	protected function setMyBbPrivateMessages($privateMessages) {
		$this->mybbDb->query('TRUNCATE mybb_privatemessages;');
		foreach($privateMessages as $privateMessage) {
			$this->printVerboseLine('++ PrivateMessage: ' . $privateMessage['subject']);
			$query = 'INSERT INTO mybb_privatemessages (pmid, uid, fromid, recipients, folder, subject, message, dateline, status) 
			VAlUES(
				"' . $this->mybbDb->real_escape_string($privateMessage['pmID']) . '", 
				"' . $this->mybbDb->real_escape_string($privateMessage['userID']) . '", 
				"' . $this->mybbDb->real_escape_string($privateMessage['userID']) . '", 
				"' . $this->mybbDb->real_escape_string($privateMessage['target']) . '", 
				' . 0 . ',
				"' . $this->mybbDb->real_escape_string($privateMessage['subject']) . '",
				"' . $this->mybbDb->real_escape_string($privateMessage['message']) . '",
				"' . $this->mybbDb->real_escape_string($privateMessage['time']) . '",
				1
			);';
			if(!$this->mybbDb->query($query))
				throw new Exception('PrivateMessage Query failed: ' . $this->mybbDb->error . PHP_EOL . $query);
		}
	}

	/**
	 * function printLine
	 *
	 * @param string $text
	 * @todo Switch CLI and HTML
	 * @return void
	 */
	protected function printLine($text) {
		echo $text . PHP_EOL;
	}

	/**
	 * function printVerboseLine
	 * Prints text only if current mode is verbose
	 *
	 * @param string $text
	 * @return void
	 */
	protected function printVerboseLine($text) {
		if($this->verbose)
			$this->printLine($text);
	}

}

$tmp = new WBBLite2Exporter_MyBBImporter();
exit($tmp->run());
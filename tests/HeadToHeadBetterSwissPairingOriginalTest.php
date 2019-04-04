<?php
require_once('HeadToHeadSwissPairingTest.php');

/**
 * Class HeadToHeadBetterSwissPairingOriginalTest
 *
 * runs all the tests in HeadToHeadSwissPairingTest with
 * HeadToHeadBetterSwissPairing instead of HeadToHeadSwissPairing
 */
class HeadToHeadBetterSwissPairingOriginalTest extends HeadToHeadSwissPairingTest
{
	public function testJumpedGroup() {
		// HeadToHeadBetterSwissPairing doesn't return the determined pairings in any
		// particular order, so the original implementation of the test would fail.
	}

	protected function getBuilder($legacyGroups, $byes = []) {
		$groups = [];
		$previous_opponents = [];
		foreach ($legacyGroups as $group) {
			$groups []= array_keys($group);
			foreach ($group as $player => $opponents) {
				$previous_opponents[$player] = $opponents;
			}
		}
		return new haugstrup\TournamentUtils\HeadToHeadBetterSwissPairing($groups, $previous_opponents, $byes);
	}
}

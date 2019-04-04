<?php
use PHPUnit\Framework\TestCase;

class HeadToHeadSwissPairingTest extends TestCase {

  /**
   * Helper function, verifies basics:
   * - One bye if odd number of players.
   * - 2-player groups.
   * - Correct number of 2-player groups.
   * - Player with bye isn't in a group.
   * - No duplicate players in groups.
   */
  private function checkResults($count, $pairings) {
    $players = array();
    $this->assertEquals($count % 2, count($pairings['byes']));
    if ($count % 2) {
      if (!isset($players[$pairings['byes'][0]])) $players[$pairings['byes'][0]] = 0;
    	$players[$pairings['byes'][0]]++;
    }
    $this->assertEquals(floor($count / 2), count($pairings['groups']));
    foreach ($pairings['groups'] as $group) {
      $this->assertEquals(2, count($group));
      foreach ($group as $player_id) {
        if (!isset($players[$player_id])) $players[$player_id] = 0;
        $players[$player_id]++;
      }
    }

    $this->assertEquals($count, array_sum($players));
  }

  // No one should get a bye if there's an even number of players.
  public function testNoByeForEvenPlayerCount() {
    $groups = array(
      array(
        'Andreas' => array('Per' => 1, 'Darren' => 1),
        'Per' => array('Matt' => 1, 'Andreas' => 1),
        'Shon' => array('Sally' => 1, 'Eric' => 1)
      ),
      array(
        'Darren' => array('Andreas' => 1, 'Sally' => 1),
        'Matt' => array('Per' => 1, 'Eric' => 1),
        'Eric' => array('Shon' => 1, 'Matt' => 1)
      )
    );
    $byes = array('Andreas' => 1, 'Shon' => 1, 'Darren' => 1, 'Matt' => 1);
    $builder = $this->getBuilder($groups, $byes);

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();
      $this->checkResults(6, $pairings);
    }
  }

  // Simulate round one of a tournament with an even number of players.
  public function testSingleGroup() {
    $groups = array(
      array(
        'Andreas' => array(),
        'Per' => array(),
        'Shon' => array(),
        'Darren' => array(),
        'Matt' => array(),
        'Eric' => array()
      )
    );
    $builder = $this->getBuilder($groups);

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();
      $this->checkResults(6, $pairings);
    }
  }

  // Handle a group with only one player
  public function testSinglePlayer() {
    $groups = array(
      array(
        'Andreas' => array(),
      ),
      array(
        'Per' => array(),
        'Shon' => array(),
        'Darren' => array(),
        'Matt' => array(),
        'Eric' => array()
      )
    );
    $builder = $this->getBuilder($groups);

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();
      $this->checkResults(6, $pairings);
    }
  }

  // Simulate round one of a tournament with an odd number of players.
  public function testSingleGroupOddCount() {
    $groups = array(
      array(
        'Andreas' => array(),
        'Per' => array(),
        'Darren' => array(),
        'Matt' => array(),
        'Eric' => array()
      )
    );
    $builder = $this->getBuilder($groups);

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();
      $this->checkResults(5, $pairings);
    }
  }

  // Player getting a bye shouldn't have more byes than another player.
  public function testDuplicateBye() {
    $groups = array(
      array(
        'Andreas' => array('Per' => 1, 'Darren' => 1),
        'Per' => array('Matt' => 1, 'Andreas' => 1),
        'Shon' => array('Sally' => 1, 'Eric' => 1)
      ),
      array(
        'Sally' => array('Darren' => 1, 'Shon' => 1),
        'Darren' => array('Andreas' => 1, 'Sally' => 1),
        'Matt' => array('Per' => 1, 'Eric' => 1),
        'Eric' => array('Shon' => 1, 'Matt' => 1)
      )
    );
    $byes = array('Andreas' => 1, 'Shon' => 1, 'Darren' => 1, 'Matt' => 1);
    $builder = $this->getBuilder($groups, $byes);

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();
      $this->checkResults(7, $pairings);
      $this->assertFalse(isset($byes[$pairings['byes'][0]]),
        $pairings['byes'][0] . ' got an extra bye');
    }
  }

  // Ensure that a player is not dropped more than one group
  // E.g. a 0 strikes player is not paired with a 2 strikes player
  public function testJumpedGroup() {
    $groups = array(
      array(
        'PlayerARank0' => array(),
        'PlayerBRank0' => array(),
        'PlayerCRank0' => array()
      ),
      array(
        'PlayerARank1' => array(),
        'PlayerBRank1' => array()
      ),
      array(
        'PlayerARank2' => array()
      ),
      array(
        'PlayerARank3' => array(),
        'PlayerBRank3' => array(),
        'PlayerCRank3' => array()
      ),
      array(
        'PlayerARank4' => array(),
        'PlayerBRank4' => array(),
        'PlayerCRank4' => array()
      ),
      array(
        'PlayerARank5' => array(),
        'PlayerBRank5' => array(),
        'PlayerCRank5' => array()
      ),
    );
    $builder = $this->getBuilder($groups);

    $group_num = 0;
    $player_group = array();
    foreach($groups as $players) {
      foreach($players as $player => $opponents) {
        $player_group[$player] = $group_num;
      }
      $group_num++;
    }

    for($i=0;$i<100;$i++) {
      $pairings = $builder->build();

      $this->checkResults(15, $pairings);

      // The min rank of a group must be >= max of prior group.  If not,
      // a player has jumped through the prior group when making pairings.
      $prior_min = 0;
      $prior_max = 0;
      $prior_group = array();
      foreach($pairings['groups'] as $group) {
        $this_min = array_reduce($group, function ($cur_rank, $player) use ($player_group) {
          return min($cur_rank, $player_group[$player]);
        }, 999);
        $this_max = array_reduce($group, function ($cur_rank, $player) use ($player_group) {
          return max($cur_rank, $player_group[$player]);
        }, 0);
        $this->assertGreaterThanOrEqual($prior_max, $this_min, print_r($group, true) . " jumped " . print_r($prior_group, true));
        $prior_min = $this_min;
        $prior_max = $this_max;
        $prior_group = $group;
      }
    }

  }

  protected function getBuilder($groups, $byes = array()) {
	  return new haugstrup\TournamentUtils\HeadToHeadSwissPairing($groups, $byes);
  }

  // Should write a test to start with an odd number of players and run
  // through a simulated 10 strike tournament and validate constraints
  // such as:
  //   maximum buys - minimum buys <= 1 (no one get a buy until everyone has one)
  //   test for testJumpedGroup and a bye with an even group of players
}

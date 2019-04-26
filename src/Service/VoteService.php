<?php

namespace App\Service;

use App\Entity\FoodVote;
use App\Exception\VotingNotAllowedException;
use App\Parser\TimeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoteService
{
    private $ipService;
    private $userService;
    private $nearplaceService;
    private $timeService;
    private $venueService;
    private $entityManager;
    private $validator;
    private $logger;

    public const VOTES_VALID_SPECIAL = ['Verweigerung', 'Egal', 'special'];

    public function __construct(IpService $ipService, UserService $userService, NearplaceService $nearplaceService, TimeService $timeService, VenueService $venueService, EntityManagerInterface $entityManager, ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->ipService = $ipService;
        $this->userService = $userService;
        $this->nearplaceService = $nearplaceService;
        $this->timeService = $timeService;
        $this->venueService = $venueService;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function votingDisplayAllowed(string $ip): bool
    {
        return $this->ipService->isInternIp($ip);
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function votingAllowed(string $ip): bool
    {
        global $timestamp;

        $voting_over_time = strtotime(getenv('VOTING_OVER_TIME'));
        return (
            $this->ipService->isInternIp($ip)
            && (date('Ymd') < date('Ymd', $timestamp)
                || (date('Ymd') === date('Ymd', $timestamp) && time() < $voting_over_time))
        );
    }

    /**
     * @param string $timestamp
     * @param string|null $ip
     * @param string|null $category
     * @return FoodVote[]
     */
    public function getAllVotes(string $timestamp, ?string $ip = null, ?string $category = null): array
    {
        try {
            if ($ip && $category) {
                return $this->entityManager->getRepository(FoodVote::class)->findBy([
                    'day' => $this->timeService->dayFromTimestamp($timestamp),
                    'user' => $this->userService->getUser($ip),
                    'category' => $category,
                ]);
            }

            if ($ip) {
                return $this->entityManager->getRepository(FoodVote::class)->findBy([
                    'day' => $this->timeService->dayFromTimestamp($timestamp),
                    'user' => $this->userService->getUser($ip),
                ]);
            }

            if ($category) {
                return $this->entityManager->getRepository(FoodVote::class)->findBy([
                    'day' => $this->timeService->dayFromTimestamp($timestamp),
                    'category' => $category,
                ]);
            }

            return $this->entityManager->getRepository(FoodVote::class)->findBy([
                'day' => $this->timeService->dayFromTimestamp($timestamp),
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);

            return [];
        }
    }

    /**
     * @param int $weekNumber
     * @param int $yearNumber
     * @return FoodVote[]
     */
    public function getAllVotesWeekly(int $weekNumber, int $yearNumber): array
    {
        $weekStartEnd = $this->timeService->getStartAndEndDate($weekNumber, $yearNumber);
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('foodVote')
            ->from(FoodVote::class, 'foodVote')
            ->add('where', $queryBuilder->expr()->between(
                'foodVote.day',
                ':weekStart',
                ':weekEnd'
            ))
            ->setParameters([
                'weekStart' => $weekStartEnd[0],
                'weekEnd' => $weekStartEnd[1]
            ]);
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    /**
     * @param string $timestamp
     * @param string|null $ip
     * @param string|null $category
     * @return array
     */
    public function getAllVoteData(string $timestamp, ?string $ip = null, ?string $category = null): array
    {
        $return = [];
        foreach ($this->getAllVotes($timestamp, $ip, $category) as $foodVote) {
            $ip = $foodVote->getUser()->getIp();
            if (!isset($return[$ip])) {
                $return[$ip] = [
                    'name' => $foodVote->getUser()->getName(),
                    'votes' => [],
                ];
            }
            $return[$ip]['votes'][$foodVote->getCategory()] = $foodVote->getVote();
        }

        return $return;
    }

    /**
     * @param int $weekNumber
     * @param int $yearNumber
     * @return array
     */
    public function getAllVoteDataWeekly(int $weekNumber, int $yearNumber): array
    {
        $return = [];
        foreach ($this->getAllVotesWeekly($weekNumber, $yearNumber) as $foodVote) {
            if (!isset($return[$foodVote->getUser()->getIp()])) {
                $return[$foodVote->getUser()->getIp()] = [
                    'name' => $foodVote->getUser()->getName(),
                    'votes' => [],
                ];
            }
            $return[$foodVote->getUser()->getIp()]['votes'][$foodVote->getCategory()] = $foodVote->getVote();
        }

        return $return;
    }

    /**
     * @param string $timestamp
     * @param string $ip
     * @param string|null $category
     */
    public function voteDelete(string $timestamp, string $ip, ?string $category = null): void
    {
        $foodVotes = $this->getAllVotes($timestamp, $ip, $category);
        foreach ($foodVotes as $foodVote) {
            $this->entityManager->remove($foodVote);
        }

        $this->entityManager->flush();
    }

    /**
     * @param string $timestamp
     * @param string $ip
     * @param string $identifier
     * @param string $vote
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface|null
     */
    public function voteHelper(string $timestamp, string $ip, string $identifier, string $vote): ?\Symfony\Component\Validator\ConstraintViolationListInterface
    {
        $foodVotes = $this->getAllVotes($timestamp, $ip, $identifier);
        $foodVote = reset($foodVotes);
        if (!$foodVote) {
            $foodUser = $this->userService->getUser($ip);
            if (!$this->entityManager->contains($foodUser)) {
                // User needs to be persisted to vote
                $this->entityManager->persist($foodUser);
            }

            $foodVote = (new FoodVote())
                ->setDay($this->timeService->dayFromTimestamp($timestamp))
                ->setUser($this->userService->getUser($ip))
                ->setCategory($identifier);
        }

        $foodVote->setVote($vote);

        $errors = $this->validator->validate($foodVote);
        if (count($errors)) {
            return $errors;
        }

        $this->entityManager->contains($foodVote) ? $this->entityManager->merge($foodVote)
            : $this->entityManager->persist($foodVote);
        $this->entityManager->flush();
        return null;
    }

    /**
     * @param $ip
     * @param array $votes
     * @param bool $display_menus
     * @param bool $show_js_actions
     * @return string
     */
    public function summaryHtml($ip, array $votes, $display_menus = false, $show_js_actions = true): string
    {
        global $timestamp;
        $html = '';

        // no votes yet
        if (!is_array($votes) || empty($votes)) {
            return "<div style='margin: 5px'>Noch keine Daten vorhanden</div>";
        }

        // minimal => no vote actions
        if (isset($_GET['minimal'])) {
            $show_js_actions = false;
        }

        // table with details
        // note: use inline style here for email
        $html .= '<table style="border-spacing: 5px" data-role="table">
		<thead>
			<tr>
				<th style="text-align: center"><b>Benutzer</b></th>
				<th style="text-align: center"><b>Vote Ups</b></th>
				<th style="text-align: center"><b>Vote Downs</b></th>
				<th style="text-align: center"><b>Notizen</b></th>
			</tr>
		</thead><tbody>';
        foreach ($votes as $user_ip => $vote_data) {
            $upVotes = array_keys($vote_data['votes'], 'up');
            $downVotes = array_keys($vote_data['votes'], 'down');
            $specialVote = $vote_data['votes']['special'] ?? null;

            sort($upVotes);
            sort($downVotes);

            // style adaptions according to vote
            if ($specialVote === self::VOTES_VALID_SPECIAL[0]) {
                $row_style = 'color: #f99';
            } elseif ($specialVote === self::VOTES_VALID_SPECIAL[1]) {
                $row_style = 'color: #999';
            } else {
                $row_style = '';
            }

            $upVotes_style = empty($upVotes) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; color: #008000;';
            $downVotes_style = empty($downVotes) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; color: #FF0000;';
            $specialVote_style = empty($specialVote) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; text-align: center;';

            $upVotes = $this->votesAdapt($upVotes, $ip, $user_ip, $show_js_actions, $upVotes_style);
            $downVotes = $this->votesAdapt($downVotes, $ip, $user_ip, $show_js_actions, $downVotes_style);

            // cleanup other data for output
            $specialVote = htmlspecialchars($specialVote);
            // replace urls with an a tag
            $specialVote = preg_replace('/(https?:\/\/\S+)/', '<a href="$1" target="_blank">$1</a>', $specialVote);
            // current user => add delete functionality
            if ($show_js_actions && $user_ip === $ip) {
                if (!empty($specialVote)) {
                    $specialVote .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'special\')" style="color: red ! important">x</a></sup>';
                } else {
                    $specialVote = '<a href="#setNoteDialog" data-rel="dialog" data-transition="pop" class="ui-link">Notiz</a>';
                }
                // otherwise => add "me too" functionality
            } else if ($show_js_actions && !empty($specialVote) && in_array($specialVote, self::VOTES_VALID_SPECIAL, true)) {
                $specialVote .= " <sup title='Selbiges voten'><a href='javascript:void(0)' onclick='vote_special(\"{$specialVote}\")' style='color: red ! important'>+1</a></sup>";
            }

            // prepare data for output
            $upVotes = empty($upVotes) ? '-' : implode(', ', $upVotes);
            $downVotes = empty($downVotes) ? '-' : implode(', ', $downVotes);
            $specialVote = empty($specialVote) ? '-' : $specialVote;

            $html .= "<tr style='$row_style'>
			<td>" . htmlspecialchars($vote_data['name']) . "</td>
			<td style='$upVotes_style'>{$upVotes}</td>
			<td style='$downVotes_style'>{$downVotes}</td>
			<td style='$specialVote_style'>{$specialVote}</td>
		</tr>";
        }
        $html .= '</tbody></table>';

        // current ranking
        $html .= '<table><tbody><tr>';
        $html .= '<td style="vertical-align: top">';
        $html .= $this->rankingSummaryHtml(
            $this->voteGetRankings($votes), 'Tages-Ranking:', $display_menus
        );
        $html .= '</td>';
        // weekly ranking
        $html .= '<td style="vertical-align: top">';
        $html .= $this->rankingSummaryHtml(
            $this->voteGetRankings(
                $this->getAllVoteDataWeekly(
                    date('W', $timestamp),
                    date('Y', $timestamp)
                )
            ), 'Wochen-Ranking:'
        );
        $html .= '</td>';
        $html .= '</tr></tbody></table>';

        return $html;
    }

    /**
     * @param string $ip
     * @throws VotingNotAllowedException
     */
    public function assertVotingAllowed(string $ip): void
    {
        if (!$this->votingAllowed($ip)) {
            throw new VotingNotAllowedException('Voting is not allowed');
        }
    }

    /**
     * @param string $ip
     * @throws VotingNotAllowedException
     */
    public function assertVotingDisplayAllowed(string $ip): void
    {
        if (!$this->votingDisplayAllowed($ip)) {
            throw new VotingNotAllowedException('Voting display is not allowed');
        }
    }

    private function votesAdapt($votes, $request_ip, $user_ip, $show_js_actions = true, $style_custom = 'text-decoration: none ! important; color: inherit ! important; font-weight: inherit ! important;')
    {
        foreach ($votes as &$venue_class) {
            $website = $this->getWebsiteFromVenueClass($venue_class);
            $title = htmlspecialchars($this->getTitleFromVenueClass($venue_class));
            // Dirty convert quotes to HTML entities in order avoid escaping problems in JS
            $venue_class = htmlspecialchars($venue_class, ENT_QUOTES);
            $venue_title = "<a href='{$website}' target='_blank' title='Homepage' style='{$style_custom}'>
				<span style='{$style_custom}'>{$title}</span>
			</a>";
            // current user => add delete functionality
            if ($show_js_actions && $user_ip === $request_ip) {
                $venue_title .= " <sup title='Löschen'><a href='javascript:void(0)' onclick='vote_delete_part(\"{$venue_class}\")' title='' style='color: red ! important'>x</a></sup>";
                // otherwise => add vote actions
            } else if ($show_js_actions) {
                $venue_title .= ' ';
                $venue_title .= "<sup title='Vote Up'><a href='javascript:void(0)' onclick='vote_up(\"{$venue_class}\")' style='color: red ! important'>+1</a></sup>";
                $venue_title .= '<sup> | </sup>';
                $venue_title .= " <sup title='Vote Down'><a href='javascript:void(0)' onclick='vote_down(\"{$venue_class}\")' style='color: red ! important'>-1</a></sup>";
            }
            $venue_class = trim($venue_title);
        }
        unset($venue_class);

        return $votes;
    }

    /**
     * Tries to get the website from the venue class
     * Searches nearplace caches or the real venue entries and uses a simple web search as fallback
     * @param string $name
     * @return string
     */
    private function getWebsiteFromVenueClass(string $name): string
    {
        $venue = $this->venueService->venueFromName($name);
        if ($venue) {
            return $venue->url;
        }

        $website = $this->nearplaceService->venueWebsite($name);

        // search provider fallback
        if ($website === null) {
            $website = getenv('SEARCH_PROVIDER') . urlencode($name);
        }

        return (string)$website;
    }

    private function getTitleFromVenueClass(string $name): string
    {
        $venue = $this->venueService->venueFromName($name);
        if (!$venue) {
            return $name;
        }

        return $venue->title;
    }

    private function voteGetRankings(array $votes, int $preserve_only_top = 3): array
    {
        // get venue ratings
        $venue_rating = [];

        foreach ($votes as $user => $vote_data) {
            if (!isset($vote_data['votes'])) {
                continue;
            }

            foreach ($vote_data['votes'] as $venue => $vote) {
                if (is_array($vote)) {
                    foreach ($vote as $vote_part) {
                        if (!isset($venue_rating[$venue])) {
                            $venue_rating[$venue] = ($vote_part === 'up') ? 1 : -1;
                        } else {
                            $venue_rating[$venue] += ($vote_part === 'up') ? 1 : -1;
                        }
                    }
                } else if ($venue !== 'special') {
                    if (!isset($venue_rating[$venue])) {
                        $venue_rating[$venue] = ($vote === 'up') ? 1 : -1;
                    } else {
                        $venue_rating[$venue] += ($vote === 'up') ? 1 : -1;
                    }
                }
            }
        }

        // remove <= 0 rankings
        foreach ($venue_rating as $key => $value) {
            if ($value <= 0) {
                unset($venue_rating[$key]);
            }
        }

        $ratings = array_values($venue_rating);
        rsort($ratings);

        // build new grouped by rating list
        $venue_rating_final = [];
        foreach ($ratings as $rating) {
            $venue_rating_final[$rating] = array_keys($venue_rating, $rating);
        }

        // only preserve top x for rating
        if ($preserve_only_top) {
            $venue_rating_final = array_slice($venue_rating_final, 0, $preserve_only_top, true);
        }

        return $venue_rating_final;
    }

    public function rankingSummaryHtml($rankings, $title, $display_menus = false, $style_custom = 'text-decoration: none ! important; color: inherit ! important; font-weight: inherit ! important;'): string
    {
        $html = '<table style="border-spacing: 5px">';
        $html .= "<tr><td><b>${title}</b></td></tr>";
        $cnt = 1;
        // get all sane rating count
        $all_sane_rating_cnt = 0;
        foreach ($rankings as $rating => $venues) {
            $all_sane_rating_cnt += $rating;
        }
        foreach ($rankings as $rating => $venues) {
            sort($venues);
            // resolve class names to venue titles
            foreach ($venues as &$venueName) {
                $website = $this->getWebsiteFromVenueClass($venueName);
                $title = htmlspecialchars($this->getTitleFromVenueClass($venueName));
                $venueName = "<a href='{$website}' target='_blank' title='Homepage' style='{$style_custom}'>"
                    . "<span style='{$style_custom}'>{$title}</span>"
                    . '</a>';
            }
            unset($venueName);

            // mark venues which got >= 50% sane ratings
            // but only if there are multiple venues
            if (($rating / $all_sane_rating_cnt) >= 0.5 && count($rankings) > 1) {
                $html .= "<tr><td title='Votes >= 50%' style='font-weight: bold;'>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
            } else {
                $html .= "<tr><td>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
            }
            $cnt++;
        }
        if (empty($rankings)) {
            $html .= '<tr><td>Kein Ergebnis</td></tr>';
        }
        $html .= '</table>';

        // print menu of rated venues (for email notifier)
        if ($display_menus) {
            $html_menu = '';
            foreach ((array)$rankings as $venues) {
                foreach ((array)$venues as $venueName) {
                    $venue = $this->venueService->venueFromName($venueName);
                    if (!$venue) {
                        continue;
                    }

                    $html_menu .= "<div style='margin: 10px 5px'><span style='font-weight: bold'>{$venue->title}</span>{$venue->getMenuData()}</div>";
                }
            }
            if (!empty($html_menu)) {
                $html .= '<br />';
                $html .= '<div style="margin: 5px; font-weight: bold">Menüs:</div>';
                $html .= $html_menu;
            }
        }
        return $html;
    }

}

<?php

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

require 'vendor/autoload.php';

// Load the .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_GET['k']) || $_GET['k'] !== $_ENV['SECRET_KEY']) {
    header('HTTP/1.0 403 Forbidden');
    echo 'access denied';
    exit();
}

// Set up the Notion API call
$client = new Client();
try {
    $response = $client->request('POST', 'https://api.notion.com/v1/databases/' . $_ENV['NOTION_DB_ID'] . '/query', [
        'body' => json_encode([
            'page_size' => 100,
            'filter' => [
                'and' => [
                    [
                        'property' => $_ENV['NOTION_DATE_PROPERTY_NAME'],
                        'date' => [
                            'is_not_empty' => true,
                        ],
                    ],
                    [
                        'property' => $_ENV['NOTION_STATUS_PROPERTY_NAME'],
                        'status' => [
                            'does_not_equal' => $_ENV['NOTION_EXCLUDE_STATUS'],
                        ]
                    ]
                ],
            ],
            'sorts' => [
                [
                    'property' => 'Date',
                    'direction' => 'descending',
                ]
            ],
        ], JSON_THROW_ON_ERROR),
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $_ENV['NOTION_API_KEY'],
            'Content-Type' => 'application/json',
            'Notion-Version' => '2022-06-28',
        ],
    ]);
} catch (GuzzleException $e) {
    die($e->getMessage());
} catch (JsonException $e) {
    die($e->getMessage());
}

$result = json_decode($response->getBody(), false);

if (null !== $result) {
    $events = [];
    foreach ($result->results as $item) {
        $date = $item->properties->{$_ENV['NOTION_DATE_PROPERTY_NAME']}->date->start;
        $title = $item->properties->Name->title[0]->text->content;
        // Create an event
        $event = new Event();
        $event->setSummary($title);
        $event->setDescription($item->url);
        $event->setOccurrence(
            new SingleDay(
                new Date(
                    DateTimeImmutable::createFromFormat('Y-m-d', $date)
                )
            )
        );
        $events[] = $event;
    }

    // Create calendar
    $calendar = new Calendar($events);
    try {
        $dateInterval = new DateInterval($_ENV['TTL'] ?? 'PT5M');
    } catch (Exception $e) {
        die($e->getMessage());
    }
    $calendar->setPublishedTTL($dateInterval);
    $componentFactory = new CalendarFactory();
    $calendarComponent = $componentFactory->createCalendar($calendar);

    if (isset($_ENV['DEBUG_MODE']) && $_ENV['DEBUG_MODE'] === 'true') {
        echo '<pre>';
        echo $calendarComponent;
        echo '</pre>';
        exit();
    }

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="cal.ics"');
    echo $calendarComponent;
}
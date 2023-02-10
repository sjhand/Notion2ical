# Notion2ical

This is a simple PHP script streaming Notion database items/pages with a date into the iCalendar format.

## Prerequisites

* PHP ^8.1
* Composer

## Installation

Install dependencies through composer.

```bash
composer install
```
Create an .env file from the example, and set up the variables explained below.

```bash
cp .env.example .env
```
Set up a [Notion integration](https://developers.notion.com/docs/create-a-notion-integration) to receive an API key.

The iCal feed will be accessible through `https://www.yourdomain.com/index.php?k=<SECRET_KEY>`.

Make sure you add the key in the 'k' GET variable.

## Environment Variables

To run this script, you will need to add the following environment variables to your .env file

`SECRET_KEY` Security key preventing public access

`NOTION_API_KEY` Notion API key

`NOTION_DB_ID` Notion database ID

`NOTION_DATE_PROPERTY_NAME` Notion property name containing the task date

`NOTION_STATUS_PROPERTY_NAME` Notion property name containing the status

`NOTION_EXCLUDE_STATUS` Notion status to exclude in the iCal feed

## Changelog

Can be found [here](CHANGELOG.md)
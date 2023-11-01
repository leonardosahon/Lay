<?php

namespace Lay\libs;

use Lay\core\traits\IsSingleton;

class LayCalendarEvent
{
    use IsSingleton;
    /**
     * @param array $options
     * @todo Checkout the official documentation and integrate more features as required. https://icalendar.org/iCalendar-Venue-Draft/2-an-example.html
     * @return string
     */
    public static function create(array $options) : string {
        $start = strtotime($options['start']);
        $end = strtotime($options['end']);

        if($start == $end || $end < $start)
            $end = $start + 1800;

        $data = [
            'uid' => uniqid(more_entropy: true),
            'start' => date("Ymd\THis\Z", $start),
            'end' => date("Ymd\THis\Z", $end),
            'summary' => substr(preg_replace("/\r/","",$options['title']), 0, 75),
            'description' => $options['desc'],
            'category' => str_replace(',', '\,', $options['category']),
            'location' => $options['location'] ?? "Online Meeting",
            'class' => strtoupper($options['class'] ?? "CONFIDENTIAL"),
            'status' => strtoupper($options['status'] ?? "CONFIRMED"),
            'organizer' => $options['organizer']
        ];

        $now = date("Ymd\THis\Z");
        $end = $data['end'] ? "DTEND:{$data['end']}" : "";

        return <<<DATA
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//hacksw/handcal//NONSGML v1.0//EN
        BEGIN:VEVENT
        UID:{$data['uid']}
        DTSTAMP:$now
        DTSTART:{$data['start']}
        $end
        CATEGORIES:{$data['category']}
        CLASS:{$data['class']}
        SUMMARY:{$data['summary']}
        DESCRIPTION:{$data['description']}
        LOCATION:{$data['location']}
        STATUS:{$data['status']}
        ORGANIZER;CN={$data['organizer']}:mailto:{$data['organizer']}
        END:VEVENT
        END:VCALENDAR
        DATA;
    }

}
<?php

// Function to standardize service hours
function standardizeServiceHours($hours_raw) {
    // Normalize input
    $hours_raw = str_replace(['<br>', "\n"], ';', $hours_raw);
    $hours_raw = trim(preg_replace('/\s+/', ' ', $hours_raw));

    // Default hours
    $days = [
        "Monday" => "Closed",
        "Tuesday" => "Closed",
        "Wednesday" => "Closed",
        "Thursday" => "Closed",
        "Friday" => "Closed",
        "Saturday" => "Closed",
        "Sunday" => "Closed",
        "Notes" => ""
    ];

    // Day mappings
    $day_map = [
        'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
        'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
        'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
        'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday',
        'sunday' => 'Sunday'
    ];

    // Handle special cases
    if (preg_match('/temporarily closed|renovation/i', $hours_raw)) {
        $days["Notes"] = $hours_raw;
        return json_encode($days);
    }

    // Split into segments
    $segments = preg_split('/[;\n]/', $hours_raw);
    $notes = [];

    foreach ($segments as $segment) {
        $segment = trim($segment);
        if (empty($segment)) continue;

        // Handle closure notes
        if (preg_match('/closed.*(sun|public holiday|saturday|tue|thu|sat|mon|wed|fri)/i', $segment)) {
            $notes[] = $segment;
            continue;
        }

        // Parse days and times
        $day_list = [];
        $time_ranges = [];

        // Extract days and times
        if (preg_match('/^(.*?)(?:[:,]\s*|\s+)(.*)$/', $segment, $matches)) {
            $day_part = trim($matches[1]);
            $time_part = trim($matches[2]);

            // Handle day ranges (e.g., Mon-Fri)
            if (preg_match('/(\w+)-(\w+)/i', $day_part, $range_match)) {
                $start_day = strtolower($range_match[1]);
                $end_day = strtolower($range_match[2]);
                if (isset($day_map[$start_day]) && isset($day_map[$end_day])) {
                    $day_order = array_keys($day_map);
                    $start_idx = array_search($day_map[$start_day], $day_order);
                    $end_idx = array_search($day_map[$end_day], $day_order);
                    for ($i = $start_idx; $i <= $end_idx && $i < 7; $i++) {
                        $day_list[] = $day_order[$i];
                    }
                }
            } else {
                // Handle comma-separated or individual days
                $days_raw = str_replace([' and ', '&'], ',', $day_part);
                $days_split = preg_split('/\s*,\s*/', $days_raw);
                foreach ($days_split as $d) {
                    $d = trim(strtolower($d));
                    if (isset($day_map[$d])) {
                        $day_list[] = $day_map[$d];
                    }
                }
            }

            // Parse time ranges
            $time_part = str_replace(['–', '—', 'to'], '-', $time_part);
            $time_segments = preg_split('/\s*(?:and|&)\s*/', $time_part);
            foreach ($time_segments as $ts) {
                if (preg_match('/(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)\s*[-]\s*(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i', $ts, $time_match)) {
                    $start_time = standardizeTime($time_match[1]);
                    $end_time = standardizeTime($time_match[2]);
                    $time_ranges[] = "$start_time-$end_time";
                } elseif (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $ts, $time_match)) {
                    $time_ranges[] = "{$time_match[1]}-{$time_match[2]}";
                }
            }
        }

        // Apply times to days
        if (!empty($day_list) && !empty($time_ranges)) {
            $combined_time = implode(',', $time_ranges);
            foreach ($day_list as $day) {
                $days[$day] = $combined_time;
            }
        } elseif (!empty($time_ranges)) {
            // Default to Mon-Fri if no days specified
            $combined_time = implode(',', $time_ranges);
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
                $days[$day] = $combined_time;
            }
        }
    }

    // Add notes
    if (!empty($notes)) {
        $days["Notes"] = implode("; ", $notes);
    }

    return json_encode($days);
}

// Helper function to standardize time to 24-hour format
function standardizeTime($time) {
    $time = str_replace(['a.m.', 'p.m.', ' '], ['AM', 'PM', ''], strtolower($time));
    if (preg_match('/(\d{1,2})(?::(\d{2}))?(am|pm)?/i', $time, $match)) {
        $hour = (int)$match[1];
        $minute = isset($match[2]) ? $match[2] : '00';
        $ampm = isset($match[3]) ? strtolower($match[3]) : '';

        if ($ampm === 'pm' && $hour < 12) $hour += 12;
        if ($ampm === 'am' && $hour == 12) $hour = 0;

        return sprintf('%02d:%02d', $hour, $minute);
    }
    return $time; // Return original if unparseable
}

// Test cases
print_r(standardizeServiceHours("Mon, Wed and Fri, 09:00 - 12:00 and 13:30 - 17:00; Closed on Tue, Thu, Sat, Sun and public holiday"));
echo "<br>";
print_r(standardizeServiceHours("Monday: 09:00 AM - 11:00 AM & 12:30 PM - 05:00 PM"));
echo "<br>";
print_r(standardizeServiceHours("Monday: 09:00-17:00\u003Cbr\u003ETuesday: 09:00-17:00\u003Cbr\u003EWednesday: 09:00-17:00\u003Cbr\u003EThursday: 09:00-17:00\u003Cbr\u003EFriday: 09:00-17:00\u003Cbr\u003ESaturday: 09:00-17:00\u003Cbr\u003ESunday: 14:00-17:00"));

?>
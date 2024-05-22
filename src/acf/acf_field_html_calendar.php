<?php

namespace postplanpro\acf;

class acf_field_html_calendar
{

    public function __construct(){

        # Add the Custom HTML field to the options page
        add_action('acf/init', [$this, 'acf_calendar']);

        # Render the Calendar in the Custom HTML field
        add_filter('acf/render_field/type=custom_html', [$this,'render_calendar'], 10, 1);

    }


    public function acf_calendar() {
        
        acf_add_local_field_group(array(
            'key' => 'group_custom_html',
            'title' => 'Release Calendar',
            'fields' => array(
                array(
                    'key' => 'field_custom_html',
                    'label' => 'All Releases',
                    'name' => 'custom_html',
                    'type' => 'custom_html', // Custom type to render HTML
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'ppp_calendar',
                    ),
                ),
            ),
        ));
    
    }



    public function render_calendar($field) {
        echo $this->display_calendar();
        echo $this->calendar_style();
    }



    public function generate_calendar($month, $year) {
        // Array of days of the week
        $daysOfWeek = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    
        // First day of the month
        $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
    
        // Number of days in the month
        $numberDays = date('t', $firstDayOfMonth);
    
        // Information about the first day of the month
        $dateComponents = getdate($firstDayOfMonth);
    
        // Name of the month
        $monthName = $dateComponents['month'];
    
        // Index value of the first day of the month (0-6, Sun-Sat)
        $dayOfWeek = $dateComponents['wday'];

         // Adjust dayOfWeek to start from Monday (1)
        $dayOfWeek = ($dayOfWeek + 6) % 7;
    
        // Get the current date
        $dateToday = date('Y-m-d');
    
        // Create the table tag opener and day headers
        $calendar = "<table class='calendar w-full border-collapse'>";
        $calendar .= "<caption class=\"text-8xl py-4 text-center\">$monthName $year</caption>";
        $calendar .= "<tr>";
    


        // Create the calendar headers
        foreach($daysOfWeek as $day) {
            $calendar .= "<th class='header p-4 bg-neutral-200'>$day</th>";
        }
    
        // Create the rest of the calendar
        $calendar .= "</tr><tr>";
    
        // The variable $dayOfWeek will make sure that there must be only 7 columns on our table
        if ($dayOfWeek > 0) {
            $calendar .= "<td class='h-24 p-2.5 border border-neutral-200 align-top' colspan='$dayOfWeek'>&nbsp;</td>";
        }
    
        // Initialize the day counter
        $currentDay = 1;
    
        // Get the month number with leading zero
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    
        while ($currentDay <= $numberDays) {
            // If the seventh column (Saturday) is reached, start a new row
            if ($dayOfWeek == 7) {
                $dayOfWeek = 0;
                $calendar .= "</tr><tr>";
            }
    
            // Get the current day with leading zero
            $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
            $date = "$year-$month-$currentDayRel";

            // Get releases for the current date
            $releases = get_posts(array(
                'post_type' => 'release',
                'posts_per_page' => -1,
                'order' => 'ASC',
                'post_status' => array('publish', 'pending', 'future', 'private'),
                'date_query' => array(
                    array(
                        'year'  => $year,
                        'month' => $month,
                        'day'   => $currentDay
                    )
                )
            ));

            $releaseTitles = '';
            if (!empty($releases)) {
                $releaseTitles .= '<div class="releases relative flex flex-col gap-2">';
                foreach ($releases as $release) {
                    $edit_link = get_edit_post_link($release->ID);
                    $date_time = new \DateTime($release->post_date);
                    $time = $date_time->format('H:i:s');
                    $release_schedule = get_field('ppp_release_schedule', $release->ID);

                    // get schedule classes
                    if (have_rows('ppp_schedule', 'option')) {
                        while (have_rows('ppp_schedule', 'option')) {
            
                            the_row();
                            $schedule_name = get_sub_field('ppp_schedule_name');
                            if ($schedule_name == $release_schedule){
                                $release_schedule_classes = get_sub_field('schedule_html_classes');
                            }
                        }
                    }

                    $releaseTitles .= '<div>';
                        $releaseTitles .= '<a href="'.$edit_link.'" class="release flex flex-col md:flex-row gap-2 w-full rounded text-white bg-neutral-800 hover:bg-neutral-400 hover:text-black px-2 py-1 '.$release_schedule_classes.'">';
                            $releaseTitles .= '<div class="release_title py-0.5">'.$release->post_title.'</div>';
                            $releaseTitles .= '<div class="release_time bg-white rounded text-black px-2 py-0.5 ml-auto max-h-6">'.$time.'</div>';
                        $releaseTitles .= '</a>';
                    $releaseTitles .= '</div>';
                }
                $releaseTitles .= '</div>';
            }

            // Check if this is today's date
            $todayClass = ($date == $dateToday) ? ' bg-green-400 text-white rounded-full py-1 px-2 text-center mr-auto' : '';
    
            $calendar .= "<td class='day relative h-24 p-2.5 border border-neutral-200 align-top' rel='$date'>";
                $calendar .= "<div class='flex flex-col'>";
                    $calendar .= "<div class='mb-2 $todayClass'>$currentDay</div>";
                    $calendar .= $releaseTitles;
                $calendar .= "</div>";
            $calendar .= "</td>";
    
            // Increment counters
            $currentDay++;
            $dayOfWeek++;
        }
    
        // Complete the row of the last week in month, if necessary
        if ($dayOfWeek != 7) { 
            $remainingDays = 7 - $dayOfWeek;
            $calendar .= "<td class='h-24 p-2.5 border border-neutral-200 align-top' colspan='$remainingDays'>&nbsp;</td>"; 
        }
    
        $calendar .= "</tr>";
        $calendar .= "</table>";
    
        return $calendar;
    }

    
    
    public function display_calendar() {
        // Check for GET parameters
        if (isset($_GET['month']) && isset($_GET['year'])) {
            $month = intval($_GET['month']);
            $year = intval($_GET['year']);
        } else {
            // Default to current month and year
            $month = date('m');
            $year = date('Y');
        }
    
        // Create previous and next month links
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;


        $calendar = '<div class="calendar-navigation text-center mb-4">';
        $calendar .= '<a class="no-underline text-blue-500 hover:underline" href="?page=ppp_calendar&month=' . $prevMonth . '&year=' . $prevYear . '">&lt;&lt; Previous</a>';
        $calendar .= ' | ';
        $calendar .= '<a class="no-underline text-blue-500 hover:underline" href="?page=ppp_calendar&month=' . $nextMonth . '&year=' . $nextYear . '">Next &gt;&gt;</a>';
        $calendar .= '</div>';
    
        // Generate the calendar
        $calendar .= $this->generate_calendar($month, $year);
    
        return $calendar;
    }
    
    
    public function calendar_style()
    {
        ob_start();
        ?>
        <script src="https://unpkg.com/tailwindcss-cdn@3.4.1/tailwindcss.js"></script>
        <style>
            /* Fixes column breaking */
            .columns-2 {
                columns: initial !important;
            }

            /* One seventh of width (not a tailwind class for that.) */
            .calendar td {
                width: 14.28%;
            }

        </style>
        <?php

        return ob_get_clean();
    }




}




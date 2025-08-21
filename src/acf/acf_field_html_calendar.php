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
        $daysOfWeek = array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN');
    
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
        
        // Get all schedules and assign colors
        $schedules = get_posts([
            'post_type' => 'schedule',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $scheduleColors = $this->generate_schedule_colors($schedules);
    
        // Create the table tag opener and day headers
        $calendar = "<div class='calendar-container relative w-full'>";
        
        // Add schedule legend
        $calendar .= $this->generate_schedule_legend($scheduleColors);
        
        $calendar .= "<div class='calendar-widget bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 overflow-hidden'>";
        
        // Header with month/year and navigation
        $calendar .= "<div class='calendar-header bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-8 text-white relative overflow-hidden'>";
        $calendar .= "<div class='absolute inset-0 bg-gradient-to-r from-indigo-600/20 to-purple-600/20'></div>";
        $calendar .= "<div class='relative z-10 flex items-center justify-between'>";
        $calendar .= "<div class='flex flex-col'>";
        $calendar .= "<h1 class='text-4xl font-bold tracking-tight text-white/90'>$monthName</h1>";
        $calendar .= "<p class='text-lg font-medium text-white/70'>$year</p>";
        $calendar .= "</div>";
        $calendar .= "<div class='flex items-center space-x-4'>";
        
        // Navigation buttons
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;
        
        $calendar .= "<a href='?page=ppp_calendar&month=$prevMonth&year=$prevYear' class='nav-btn group flex items-center justify-center w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 transition-all duration-300 hover:scale-110 hover:shadow-lg backdrop-blur-sm'>";
        $calendar .= "<svg class='w-5 h-5 text-white group-hover:scale-110 transition-transform duration-200' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'></path></svg>";
        $calendar .= "</a>";
        $calendar .= "<a href='?page=ppp_calendar&month=$nextMonth&year=$nextYear' class='nav-btn group flex items-center justify-center w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 transition-all duration-300 hover:scale-110 hover:shadow-lg backdrop-blur-sm'>";
        $calendar .= "<svg class='w-5 h-5 text-white group-hover:scale-110 transition-transform duration-200' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'></path></svg>";
        $calendar .= "</a>";
        $calendar .= "</div>";
        $calendar .= "</div>";
        $calendar .= "</div>";
        
        // Calendar grid
        $calendar .= "<div class='calendar-grid p-8'>";
        $calendar .= "<div class='grid grid-cols-7 gap-1'>";
        
        // Create the calendar headers
        foreach($daysOfWeek as $day) {
            $calendar .= "<div class='day-header p-4 text-center'>";
            $calendar .= "<span class='text-sm font-semibold text-gray-500 uppercase tracking-wider'>$day</span>";
            $calendar .= "</div>";
        }
    
        // The variable $dayOfWeek will make sure that there must be only 7 columns on our table
        if ($dayOfWeek > 0) {
            $calendar .= "<div class='empty-cell' style='grid-column: span $dayOfWeek;'></div>";
        }
    
        // Initialize the day counter
        $currentDay = 1;
    
        // Get the month number with leading zero
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    
        while ($currentDay <= $numberDays) {
            // Get the current day with leading zero
            $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
            $date = "$year-$month-$currentDayRel";

            // Get releases for the current date
            $releases = get_posts(array(
                'post_type' => ['release','config'],
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
                $releaseTitles .= '<div class="releases space-y-0.5 mt-2">';
                foreach ($releases as $release) {
                    $edit_link = get_edit_post_link($release->ID);
                    $date_time = new \DateTime($release->post_date);
                    $time = $date_time->format('H:i');
                    $release_schedule = get_field('ppp_release_schedule', $release->ID);

                    // Check if the item is in the past
                    $current_time = new \DateTime();
                    $is_past = $date_time < $current_time;
                    $past_class = $is_past ? 'past-item' : '';

                    // Get schedule color
                    $scheduleColor = isset($scheduleColors[$release_schedule]) ? $scheduleColors[$release_schedule] : '#6b7280';

                    $releaseTitles .= '<div class="release-item group ' . $past_class . '">';
                    $releaseTitles .= '<a href="'.$edit_link.'" class="block w-full hover:bg-gray-800 transition-colors duration-200 rounded release-link ' . $past_class . '">';
                    $releaseTitles .= '<div class="flex items-center gap-2">';
                    $releaseTitles .= '<div class="schedule-dot w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: '.$scheduleColor.';"></div>';
                    $releaseTitles .= '<div class="flex-1 min-w-0">';
                    $releaseTitles .= '<div class="text-xs font-medium text-gray-700 release-title transition-colors duration-200 truncate ' . $past_class . '"><span class="text-gray-500 release-time mr-1">'.$time.'</span>'.$release->post_title.'</div>';
                    $releaseTitles .= '</div>';
                    $releaseTitles .= '</div>';
                    $releaseTitles .= '</a>';
                    $releaseTitles .= '</div>';
                }
                $releaseTitles .= '</div>';
            }

            // Check if this is today's date
            $todayClass = ($date == $dateToday) ? 'bg-white today-cell' : 'bg-white';
            $todayTextClass = ($date == $dateToday) ? 'text-indigo-600 font-bold' : 'text-gray-700 font-medium';
    
            $calendar .= "<div class='day-cell group relative min-h-[120px] p-3 rounded-xl border border-gray-100 $todayClass'>";
            $calendar .= "<div class='flex flex-col h-full'>";
            $calendar .= "<div class='day-number flex items-center justify-center w-8 h-8 rounded-full $todayTextClass text-sm font-semibold mb-2'>$currentDay</div>";
            $calendar .= $releaseTitles;
            $calendar .= "</div>";
            $calendar .= "</div>";
    
            // Increment counters
            $currentDay++;
            $dayOfWeek++;
            
            // Start new row if needed
            if ($dayOfWeek == 7) {
                $dayOfWeek = 0;
            }
        }
    
        // Complete the row of the last week in month, if necessary
        if ($dayOfWeek != 0) { 
            $remainingDays = 7 - $dayOfWeek;
            $calendar .= "<div class='empty-cell' style='grid-column: span $remainingDays;'></div>"; 
        }
    
        $calendar .= "</div>";
        $calendar .= "</div>";
        $calendar .= "</div>";
        $calendar .= "</div>";
    
        return $calendar;
    }

    private function generate_schedule_colors($schedules) {
        $colors = [
            '#3b82f6', // blue
            '#8b5cf6', // purple
            '#06b6d4', // cyan
            '#10b981', // emerald
            '#f59e0b', // amber
            '#ef4444', // red
            '#ec4899', // pink
            '#84cc16', // lime
            '#f97316', // orange
            '#6366f1', // indigo
            '#14b8a6', // teal
            '#a855f7', // violet
        ];
        
        $scheduleColors = [];
        $colorIndex = 0;
        
        foreach ($schedules as $schedule) {
            $scheduleName = get_field('ppp_schedule_name', $schedule->ID);
            if ($scheduleName) {
                $scheduleColors[$scheduleName] = $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }
        }
        
        return $scheduleColors;
    }

    private function generate_schedule_legend($scheduleColors) {
        if (empty($scheduleColors)) {
            return '';
        }
        
        $legend = '<div class="schedule-legend mb-6 p-6 bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg border border-white/20">';
        $legend .= '<h3 class="text-lg font-semibold text-gray-800 mb-4">Schedule Types</h3>';
        $legend .= '<div class="flex flex-wrap gap-3">';
        
        foreach ($scheduleColors as $scheduleName => $color) {
            $legend .= '<div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200 shadow-sm">';
            $legend .= '<div class="schedule-dot w-3 h-3 rounded-full" style="background-color: '.$color.';"></div>';
            $legend .= '<span class="text-sm font-medium text-gray-700">'.$scheduleName.'</span>';
            $legend .= '</div>';
        }
        
        $legend .= '</div>';
        $legend .= '</div>';
        
        return $legend;
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
    
        $calendar = '<div class="calendar-page min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-100 p-4 sm:p-6 lg:p-8">';
        $calendar .= '<div class="w-full max-w-none">';
        
        // Page header
        $calendar .= '<div class="text-center mb-8 sm:mb-12">';
        $calendar .= '<h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-4">Release Calendar</h1>';
        $calendar .= '<p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">Manage and visualize your content schedule with our premium calendar interface</p>';
        $calendar .= '</div>';
        
        // Generate the calendar
        $calendar .= $this->generate_calendar($month, $year);
        
        $calendar .= '</div>';
        $calendar .= '</div>';
    
        return $calendar;
    }
    
    
    public function calendar_style()
    {
        ob_start();
        ?>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        animation: {
                            'fade-in': 'fadeIn 0.5s ease-in-out',
                            'slide-up': 'slideUp 0.3s ease-out',
                            'scale-in': 'scaleIn 0.2s ease-out',
                        },
                        keyframes: {
                            fadeIn: {
                                '0%': { opacity: '0' },
                                '100%': { opacity: '1' },
                            },
                            slideUp: {
                                '0%': { transform: 'translateY(10px)', opacity: '0' },
                                '100%': { transform: 'translateY(0)', opacity: '1' },
                            },
                            scaleIn: {
                                '0%': { transform: 'scale(0.95)', opacity: '0' },
                                '100%': { transform: 'scale(1)', opacity: '1' },
                            },
                        },
                    },
                },
            }
        </script>
        <style>
            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
            }
            
            /* Glassmorphism effect */
            .calendar-widget {
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
            }
            
            /* Smooth transitions */
            .day-cell {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .day-cell:hover {
                transform: none;
                box-shadow: none;
            }
            
            /* Navigation button animations */
            .nav-btn {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .nav-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 10px 25px -5px rgba(255, 255, 255, 0.3);
            }
            
            /* Release item animations */
            .release-item {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .release-item:hover {
                transform: translateY(-1px);
            }
            
            /* Release item hover states - only for individual items */
            .release-link:hover {
                background-color: #1f2937 !important;
            }
            
            .release-link:hover .release-title {
                color: #ffffff !important;
            }
            
            .release-link:hover .release-time {
                color: #d1d5db !important;
            }
            
            /* Past item styling */
            .past-item {
                opacity: 0.5;
                filter: grayscale(50%);
            }
            
            .past-item .release-title,
            .past-item .release-time {
                color: #9ca3af !important;
            }
            
            .past-item .schedule-dot {
                opacity: 0.5;
            }
            
            .past-item:hover {
                opacity: 0.7;
                filter: grayscale(30%);
            }
            
            .past-item:hover .release-title,
            .past-item:hover .release-time {
                color: #d1d5db !important;
            }
            
            /* Today cell gradient border */
            .today-cell {
                position: relative;
                border: 2px solid transparent;
                background-clip: padding-box;
            }
            
            .today-cell::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: -1;
                margin: -2px;
                border-radius: inherit;
                background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
                background-size: 200% 200%;
                animation: gradient-shift 3s ease infinite;
            }
            
            @keyframes gradient-shift {
                0% {
                    background-position: 0% 50%;
                }
                50% {
                    background-position: 100% 50%;
                }
                100% {
                    background-position: 0% 50%;
                }
            }
            
            /* Line clamp for text overflow */
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            /* Responsive grid */
            @media (max-width: 768px) {
                .calendar-grid {
                    padding: 1rem;
                }
                
                .day-cell {
                    min-height: 100px;
                    padding: 0.5rem;
                }
                
                .calendar-header {
                    padding: 1.5rem;
                }
                
                .calendar-header h1 {
                    font-size: 1.5rem;
                }
                
                .schedule-legend {
                    margin-bottom: 1rem;
                    padding: 1rem;
                }
                
                .schedule-legend h3 {
                    font-size: 1rem;
                }
            }
            
            /* Extra small screens */
            @media (max-width: 640px) {
                .calendar-grid {
                    padding: 0.5rem;
                }
                
                .day-cell {
                    min-height: 80px;
                    padding: 0.25rem;
                }
                
                .day-number {
                    width: 1.5rem;
                    height: 1.5rem;
                    font-size: 0.75rem;
                }
                
                .schedule-dot {
                    width: 0.5rem;
                    height: 0.5rem;
                }
                
                .releases {
                    margin-top: 0.5rem;
                }
            }
            
            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .calendar-page {
                    background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
                }
                
                .calendar-widget {
                    background: rgba(30, 41, 59, 0.8);
                    border-color: rgba(255, 255, 255, 0.1);
                }
                
                .day-cell {
                    background: rgba(51, 65, 85, 0.8);
                    border-color: rgba(255, 255, 255, 0.1);
                }
                
                .day-cell:hover {
                    background: rgba(71, 85, 105, 0.8);
                }
            }
        </style>
        <?php

        return ob_get_clean();
    }




}




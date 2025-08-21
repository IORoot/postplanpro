<?php

namespace postplanpro\lib;


class update_preview
{

    public $fields;

    public function __construct($fields)
    {
        if (!$fields){ return false; }
        $this->fields = $fields;
        $this->create_content();
        $this->update_content();
    }

    private function create_content() {
        
        if (!isset($this->fields["schedule"]["ppp_social_platforms"]) || !$this->fields["schedule"]["ppp_social_platforms"]) { 
            error_log('No social platforms found in schedule for preview generation');
            return; 
        }

        $brandColors = [
            "instagram" => "bg-gradient-to-r from-pink-500 to-purple-600",
            "youtube" => "bg-red-600 text-white",
            "googlemybusiness" => "bg-blue-500",
            "twitter" => "bg-sky-500",
            "slack" => "bg-purple-500",
            "linkedin" => "bg-blue-700",
            "tiktok" => "bg-black text-white", // TikTok uses black, so we set text color too
            "facebook" => "bg-blue-700 text-white", // TikTok uses black, so we set text color too
        ];

        $defaultColor = "bg-gray-500";


        ob_start();
        $htmlContent = '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <script src="https://cdn.tailwindcss.com"></script>
                <title>Grid Layout</title>
            </head>
            <body class="bg-gray-100">
                <div class="grid grid-cols-3 gap-4 p-4 w-full max-w-6xl">';


                foreach ($this->fields["schedule"]["ppp_social_platforms"] as $platformName => $platform) {
                    if (!isset($platform["ppp_social_platform_preview_classes"]) || 
                        !isset($platform["ppp_social_platform_template_header"]) || 
                        !isset($platform["ppp_social_platform_template_footer"]) || 
                        !isset($platform["ppp_social_platform_template_hashtags"])) {
                        error_log('Missing required platform fields for preview: ' . $platformName);
                        continue;
                    }

                    $bgColor = $brandColors[strtolower($platformName)] ?? $defaultColor; // Use matching color or default
                    // override colours if set.
                    if ($platform["ppp_social_platform_preview_classes"]) {
                        $bgColor = $platform["ppp_social_platform_preview_classes"];
                    }

                    $htmlContent .= '
                        <div class="' . $bgColor . ' p-4 shadow-md rounded-md">
                            <h2 class="text-lg font-semibold mb-4">' . $platformName . '</h2>
                            <div class="text-sm text-gray-100 flex flex-col gap-2 mb-2">
                                <div class="bg-neutral-800 w-full h-40 rounded">
                                    <svg role="img" class="col-span-1 w-40 h-40 m-auto fill-neutral-600"><use xlink:href="#icon-play-circle"></use></svg>
                                    <svg id="svg-icon-play-circle" xmlns="http://www.w3.org/2000/svg" style="display:none;">
                                    <symbol id="icon-play-circle" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <path d="M10,16.5V7.5L16,12M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"></path>
                                    </symbol>
                                </svg>
                                </div>
                                
                                <div class="text-xs">' . $platform["ppp_social_platform_template_header"] . '</div>
                                <div class="text-xs">' . nl2br($this->fields["post"]->post_content) . '</div>
                                <div class="text-xs">' . $platform["ppp_social_platform_template_footer"] . '</div>
                                <div>' . $platform["ppp_social_platform_template_hashtags"] . '</div>
                            </div>
                            <div class="flex flex-col gap-2">
                    ';
                        
                    if (isset($platform["ppp_social_platform_additional_fields"]) && is_array($platform["ppp_social_platform_additional_fields"])) {
                        foreach ($platform["ppp_social_platform_additional_fields"] as $additional_key => $additional_value){
                            $htmlContent .= '
                                <div class="flex gap-2 text-xs bg-gray-300 text-black p-1 rounded">
                                    <div class="p-0.5 rounded">'.$additional_key.'</div>
                                    <div class="bg-gray-100 p-0.5 rounded">'.$additional_value.'</div>
                                </div>
                            ';
                        }
                    }

                     $htmlContent .= '</div></div>';       
                }

            $htmlContent .= '
                </div>
            </body>
            </html>
            ';

        echo '<iframe srcdoc="' . htmlspecialchars($htmlContent, ENT_QUOTES, 'UTF-8') . '" width="100%" height="600px" style="border: none; overflow: auto;" scrolling="auto"></iframe>';

        $this->html = ob_get_contents();
        ob_end_clean();

    }

    private function update_content() {
        if (!isset($this->html) || empty($this->html)) {
            error_log('No HTML content to update for preview');
            return;
        }
        update_field('ppp_preview', $this->html, $this->fields["post"]->ID);
        error_log('Successfully updated ppp_preview for post ' . $this->fields["post"]->ID);
    }


}

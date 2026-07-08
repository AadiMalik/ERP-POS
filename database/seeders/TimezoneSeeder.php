<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Timezone;
use DateTimeZone;
use DateTime;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $date = new DateTime();

        foreach (DateTimeZone::listIdentifiers() as $timezone) {

            $tz = new DateTimeZone($timezone);

            $offset = $tz->getOffset($date);

            $hours = floor(abs($offset) / 3600);
            $minutes = floor((abs($offset) % 3600) / 60);

            $formatted_offset = sprintf(
                '%s%02d:%02d',
                $offset >= 0 ? '+' : '-',
                $hours,
                $minutes
            );

            Timezone::updateOrCreate(
                [
                    'name' => $timezone,
                ],
                [
                    'offset' => $formatted_offset,
                    'timezone_id' => Timezone::where('name', $timezone)->value('timezone_id') ?? generateUuid(),
                ]
            );
        }
    }
}

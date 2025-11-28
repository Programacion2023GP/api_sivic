<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
     CREATE 
   
VIEW  `penalties_latest_view` AS
    SELECT 
        `p`.`id` AS `id`,
        `p`.`time` AS `time`,
        `p`.`date` AS `date`,
        `p`.`image_penaltie` AS `image_penaltie`,
        `p`.`images_evidences` AS `images_evidences`,
        `p`.`images_evidences_car` AS `images_evidences_car`,
        `ppd`.`person_contraloria` AS `person_contraloria`,
        `ppd`.`oficial_payroll` AS `oficial_payroll`,
        `ppd`.`person_oficial` AS `person_oficial`,
        `p`.`vehicle_service_type` AS `vehicle_service_type`,
        `p`.`alcohol_concentration` AS `alcohol_concentration`,
        `ppd`.`group` AS `group`,
        `p`.`municipal_police` AS `municipal_police`,
        `ppd`.`civil_protection` AS `civil_protection`,
        `ppd`.`command_vehicle` AS `command_vehicle`,
        `ppd`.`command_troops` AS `command_troops`,
        `ppd`.`command_details` AS `command_details`,
        `ppd`.`filter_supervisor` AS `filter_supervisor`,
        `p`.`detainee_released_to` AS `detainee_released_to`,
        `p`.`name` AS `name`,
        `p`.`lat` AS `lat`,
        `p`.`lon` AS `lon`,
        `p`.`cp` AS `cp`,
        `p`.`city` AS `city`,
        `p`.`age` AS `age`,
        `p`.`amountAlcohol` AS `amountAlcohol`,
        `p`.`number_of_passengers` AS `number_of_passengers`,
        `p`.`plate_number` AS `plate_number`,
        `p`.`detainee_phone_number` AS `detainee_phone_number`,
        `p`.`curp` AS `curp`,
        `p`.`observations` AS `observations`,
        `p`.`active` AS `active`,
        `p`.`created_by` AS `created_by`,
        `p`.`created_at` AS `created_at`,
        `p`.`updated_at` AS `updated_at`,
        `ppd`.`doctor_id` AS `doctor_id`,
        `d`.`name` AS `doctor`,
        `d`.`certificate` AS `cedula`,
        `u`.`fullName` AS `created_by_name`,
        `u`.`dependence_id` AS `user_dependence_id`,
        `u`.`role` AS `user_role`,
        `ppd`.`init_date` AS `init_date`,
        `ppd`.`final_date` AS `final_date`,
        `ppd`.`user_id` AS `auth_id`,
        `ppd`.`id` AS `penalty_preload_data_id`,
        (CASE
            WHEN (`hist`.`total_penalties` > 1) THEN 1
            ELSE 0
        END) AS `has_history`
    FROM
        ((((`penalties` `p`
        JOIN `users` `u` ON ((`p`.`created_by` = `u`.`id`)))
        LEFT JOIN `penalty_preload_data` `ppd` ON ((`p`.`penalty_preload_data_id` = `ppd`.`id`)))
        LEFT JOIN `doctor` `d` ON ((`ppd`.`doctor_id` = `d`.`id`)))
        LEFT JOIN (SELECT 
            `p2`.`curp` AS `curp`,
                `u2`.`dependence_id` AS `dependence_id`,
                COUNT(0) AS `total_penalties`
        FROM
            (`penalties` `p2`
        JOIN `users` `u2` ON ((`p2`.`created_by` = `u2`.`id`)))
        WHERE
            (`p2`.`active` = 1)
        GROUP BY `p2`.`curp` , `u2`.`dependence_id`) `hist` ON (((`hist`.`curp` = `p`.`curp`)
            AND (`hist`.`dependence_id` = `u`.`dependence_id`))))
    WHERE
        (`p`.`active` = 1)
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties_latest_view');
    }
};

<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Models\SuspiciousTopic;
use App\Models\Topic;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;

class SuspiciousTopicController extends Controller
{
    //
      //this method is used to get post by topic per day per week per month if post_count < 500 then save the records to suspicious_topics
      public function checkPostByTopic($currentTime)
      {
          $suspiciousTopicModel   =   new SuspiciousTopic();
          $suspiciousTopicModel->refreshRecords();

          $allTopics    =   Topic::all();
          foreach($allTopics as $topic)
          {
              $success    =   false;
              /**
               * last 24 hours
               */
              $from                   =   Carbon::now(new DateTimeZone('UTC'))->subDays(1)->format('Y-m-d h:m:s');
              $specificTopicWithCount =   $topic-> loadCount(['TopicPost' => function ($query) use($currentTime,$from) {
                  $query->whereBetween('created_at', [$from,$currentTime]);
              }]);
              if($specificTopicWithCount->topic_post_count < 500)
              {

                  $topicData['no_of_post_per_day']    =   $specificTopicWithCount->topic_post_count;
                  $success = true;
              }


               /**
               * last 7 days
               */
              $from                   =   Carbon::now(new DateTimeZone('UTC'))->subDays(7)->format('Y-m-d h:m:s');
              $specificTopicWithCount =   $topic-> loadCount(['TopicPost' => function ($query) use($currentTime,$from) {
                  $query->whereBetween('created_at', [$from,$currentTime]);
              }]);
              if($specificTopicWithCount->topic_post_count < 500)
              {

                  $topicData['no_of_post_per_week']    =   $specificTopicWithCount->topic_post_count;
                  $success = true;

              }

              /**
               * last month
               */
              $from                   =   Carbon::now(new DateTimeZone('UTC'))->subMonth()->format('Y-m-d h:m:s');
              $specificTopicWithCount =   $topic-> loadCount(['TopicPost' => function ($query) use($currentTime,$from) {
                  $query->whereBetween('created_at', [$from,$currentTime]);
              }]);
              if($specificTopicWithCount->topic_post_count < 500)
              {
                  $topicData['no_of_post_per_month']    =   $specificTopicWithCount->topic_post_count;
                  $success = true;

              }

              if($success)
              {
                  $topicData['topic_id']      =   $topic->id;
                  $topicData['status']        =   Constants::POSTS_TYPE_ACTIVE;
                  $topicData['created_at']    =   $currentTime;
                  $topicData['updated_at']    =   $currentTime;
                  $suspiciousTopicModel->createRecord($topicData);
              }

          }
          // return $allTopics;
      }
}

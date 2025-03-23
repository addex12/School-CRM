<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('surveys.{surveyId}', function ($user, $surveyId) {
    // Logic to determine if the user can access the survey channel
    return true; // Adjust this logic as needed
});
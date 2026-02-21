<?php
/**
 * Subscription Tier Limits — Paid Member Subscriptions
 * Plans: Individual ($15) · Family ($25) · Total ($35)
 */

// Retrieve the active PMS subscription plan for the current user
function mylog_get_pms_subscription_plan() {
    if (!is_user_logged_in()) {
        return null;
    }

    if (!function_exists('pms_get_member_subscriptions') || !function_exists('pms_get_subscription_plan')) {
        return null;
    }

    $subscriptions = pms_get_member_subscriptions([
        'user_id' => get_current_user_id(),
        'status'  => 'active',
    ]);

    if (empty($subscriptions)) {
        return null;
    }

    $plan = pms_get_subscription_plan($subscriptions[0]->subscription_plan_id);
    if (!$plan) {
        return null;
    }

    return [
        'name'  => $plan->name,
        'price' => floatval($plan->price),
    ];
}

// Returns true if the current user is allowed to add another person
function mylog_check_user_limit() {
    if (!is_user_logged_in()) {
        return false;
    }
    if (current_user_can('administrator')) {
        return true;
    }

    $plan = mylog_get_pms_subscription_plan();
    if (!$plan) {
        return false;
    }

    $current_count = count(mylog_get_accessible_users());
    return $current_count < mylog_get_limit_from_price($plan['price']);
}

// Returns the profile limit for a given plan price
function mylog_get_limit_from_price($price) {
    if ($price >= 35.00) return 5; // Total plan
    if ($price >= 25.00) return 3; // Family plan
    if ($price >= 15.00) return 1; // Individual plan
    return 0;
}

// Returns the profile limit for the current user (or 'Unlimited' for admins)
function mylog_get_user_limit() {
    if (!is_user_logged_in()) {
        return 0;
    }
    if (current_user_can('administrator')) {
        return 'Unlimited';
    }

    $plan = mylog_get_pms_subscription_plan();
    if (!$plan) {
        return 0;
    }

    return mylog_get_limit_from_price($plan['price']);
}

// Returns remaining available slots for the current user
function mylog_get_remaining_slots() {
    $limit = mylog_get_user_limit();

    if ($limit === 'Unlimited') {
        return 'Unlimited';
    }

    $current_count = count(mylog_get_accessible_users());
    return max(0, $limit - $current_count);
}

// Inject plan capacity into dashboard stats
add_filter('mylog_dashboard_stats', function($stats) {
    $limit       = mylog_get_user_limit();
    $current     = count(mylog_get_accessible_users());
    $limit_label = $limit === 'Unlimited' ? '∞' : $limit;

    $stats['user_limit'] = [
        'label' => 'Plan Capacity',
        'value' => $current . ' / ' . $limit_label,
        'color' => '#3b82f6',
    ];

    return $stats;
});
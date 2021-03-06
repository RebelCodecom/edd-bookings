<?php

use \Aventura\Diary\DateTime\Duration;
use \Aventura\Edd\Bookings\Model\Booking;

// Ensure that the booking data exists
if (!isset($data['booking']) || !($data['booking'] instanceof Booking)) {
    return;
}

$booking = $data['booking'];
$eddHtml = new \EDD_HTML_Elements();
$dateTimeFormat = 'Y-m-d H:i:s';
// Prepare booking start datetime text
$start = eddBookings()->utcTimeToServerTime($booking->getStart());
$startText = $booking->getId()
    ? $start->format($dateTimeFormat)
    : '';
// Prepare booking end datetime text
$end = eddBookings()->utcTimeToServerTime($booking->getEnd())->plus(new Duration(1));
$endText = $booking->getId()
    ? $end->format($dateTimeFormat)
    : '';
$serverTz = eddBookings()->getServerTimezoneOffsetSeconds();
?>

<input type="hidden" id="server-tz" value="<?php echo esc_attr($serverTz); ?>" />
<div class="edd-bk-booking-details">

    <p class="edd-bk-required-msg"><?php _e('The settings marked with an asterisk (*) are required for a booking to be created. Other settings are optional.', 'eddbk') ?></p>
    <hr/>

    <h4><?php _e('Payment and Service', 'eddbk'); ?></h4>
    <div>
        <label for="payment">
            <span><?php _e('Payment #', 'eddbk'); ?></span>
            <?php
                echo eddBookings()->adminTooltip(
                    __('The ID of the EDD Payment associated with this booking.', 'eddbk')
                );
            ?>
        </label>
        <input
            id="payment"
            name="payment_id"
            type="number"
            value="<?php echo esc_attr($booking->getPaymentId()); ?>"
        />
    </div>
    <div>
        <label for="service">
            <span><?php _e('Service', 'eddbk'); ?></span>
            <?php
                echo eddBookings()->adminTooltip(
                    __('The Download being provided as a service for this booking. ', 'eddbk')
                );
            ?>
        </label>
        <?php
            echo $eddHtml->product_dropdown(array(
                'id'       => 'service',
                'class'    => 'service-id',
                'name'     => 'service_id',
                'selected' => $booking->getServiceId()
        ));
        ?>
    </div>
    <div>
        <p id="service-info-loading">
            <i class="fa fa-spinner fa-spin"></i>
            <?php _e('Getting service information ...', 'eddbk'); ?>
        </p>
        <p id="service-info-msg-singular" class="info-msg">
            <i class="fa fa-info-circle"></i>
            <?php
                printf(
                    _x(
                        'The %1$s service uses a session length of %2$s %3$s and customers can book %4$s session(s).',
                        'Example: The Bike Renta; service uses a session length of 30 minutes and cusomters can book 3 session(s).',
                        'eddbk'
                    ),
                    '<span class="service-name"></span>',
                    '<span class="session-length"></span>',
                    '<span class="session-unit"></span>',
                    '<span class="num-sessions"></span>'
                );
            ?>
        </p>
        <p id="service-info-msg-plural" class="info-msg">
            <i class="fa fa-info-circle"></i>
            <?php
                printf(
                    _x(
                        'The %1$s service uses a session length of %2$s %3$s and customers can book between %4$s and %5$s sessions.',
                        'Example: The Bike Renta; service uses a session length of 2 hours and cusomters can book between 1 and 2 sessions',
                        'eddbk'
                    ),
                    '<span class="service-name"></span>',
                    '<span class="session-length"></span>',
                    '<span class="session-unit"></span>',
                    '<span class="min-sessions"></span>',
                    '<span class="max-sessions"></span>'
                );
            ?>
        </p>
        <p id="service-info-bookings-disabled" class="info-msg">
            <i class="fa fa-warning"></i>
            <?php
               _e('This service does not have bookings enabled. However, you can still create bookings for it.', 'eddbk');
            ?>
        </p>
    </div>

    <hr/>

    <h4>
        <?php _e('Customer', 'eddbk'); ?>
        <small>
            <a id="choose-customer" class="edd-bk-if-create-customer" href="javascript:void(0)">
                <i class="fa fa-mouse-pointer"></i> <?php _e('Choose an existing customer', 'eddbk'); ?>
            </a>
            <a id="create-customer" class="edd-bk-if-choose-customer" href="javascript:void(0)">
                <i class="fa fa-plus"></i> <?php _e('Create new customer', 'eddbk'); ?>
            </a>
        </small>
    </h4>

    <div class="edd-bk-if-choose-customer">
        <label for="customer">
            <span><?php _e('Existing Customer', 'eddbk'); ?></span>
            <?php
                echo eddBookings()->adminTooltip(
                    __('Choose the customer associated with this booking or create a new one.', 'eddbk')
                );
            ?>
        </label>
        <?php
            echo $eddHtml->customer_dropdown(array(
                'id'       => 'customer',
                'class'    => 'customer-id',
                'name'     => 'customer_id',
                'chosen'   => false,
                'selected' => $booking->getCustomerId()
            ));
        ?>
    </div>

    <div class="edd-bk-if-create-customer edd-bk-inline-create-customer">
        <?php echo eddBookings()->renderView('Admin.Bookings.Edit.InlineCreateCustomer', $data); ?>
    </div>

    <hr/>

    <h4><?php _e('Booking Details', 'eddbk'); ?></h4>
    <div>
        <label for="start">
            <span>
                <?php _e('Start', 'eddbk'); ?>
                <span class="required">*</span>
            </span>
            <?php
                echo eddBookings()->adminTooltip(
                    __('The date and time when this booking begins, relative to your server timezone.', 'eddbk')
                );
            ?>
        </label>
        <input
            id="start"
            name="start"
            class="edd-bk-datetime"
            type="text"
            value="<?php echo esc_attr($startText); ?>"
            required="required"
        />
    </div>
    <div class="advanced-times">
        <label></label>
        <div>
            <p id="end-utc" class="utc-time">
                <?php _e('Universal Time:', 'eddbk'); ?>
                <code>...</code>
            </p>
            <p id="end-customer" class="customer-time">
                <?php _e('Customer Time:', 'eddbk'); ?>
                <code>...</code>
            </p>
        </div>
    </div>

    <div>
        <label for="end">
            <span>
                <?php _e('End', 'eddbk'); ?>
                <span class="edd-bk-required">*</span>
            </span>
            <?php
                echo eddBookings()->adminTooltip(
                    __('The date and time when the booking ends, relative to your server timezone.', 'eddbk')
                );
            ?>
        </label>
        <input
            id="end"
            name="end"
            class="edd-bk-datetime"
            type="text"
            value="<?php echo esc_attr($endText); ?>"
            required="required"
        />
    </div>
    <div class="advanced-times">
        <label></label>
        <div>
            <p id="end-utc" class="utc-time">
                <?php _e('Universal Time:', 'eddbk'); ?>
                <code>...</code>
            </p>
            <p id="end-customer" class="customer-time">
                <?php _e('Customer Time:', 'eddbk'); ?>
                <code>...</code>
            </p>
        </div>
    </div>

    <div>
        <label for="duration">
            <?php _e('Duration', 'eddbk'); ?>
        </label>
        <code id="duration"></code>
    </div>

    <div>
        <label for="customer_tz">
            <span><?php _e('Customer Timezone', 'eddbk'); ?></span>
            <?php
                echo eddBookings()->adminTooltip(
                    __("The customer's timezone. This is optional and is only used if the service is configured to allow local times to be shown to customers.", 'eddbk')
                );
            ?>
        </label>
        <?php
            echo eddBookings()->renderView('Fragment.TimezoneOffsetDropdown', array(
                'name'     => 'customer_tz',
                'id'       => 'customer_tz',
                'selected' => $booking->getClientTimezone()
            ));
        ?>
    </div>

</div>

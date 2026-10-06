<?php

declare(strict_types=1);

/** Shared type-specific panels for offering form. */
?>
<div class="lb-offer-wizard-details" data-offer-wizard-details>
    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="product"<?= $type !== 'product' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Product details</h3>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-variants">Sizes, colours, or variants</label>
            <input id="offer-variants" class="<?= e($inputClass) ?>" name="variants" type="text" value="<?= e((string) ($config['variants'] ?? '')) ?>" placeholder="e.g. S–XL, Black, Navy">
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-delivery">Delivery / fulfillment</label>
            <input id="offer-delivery" class="<?= e($inputClass) ?>" name="delivery_notes" type="text" value="<?= e((string) ($config['delivery_notes'] ?? '')) ?>" placeholder="e.g. Lagos delivery in 2–3 days">
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-purchase-url">Purchase link <span class="lb-offer-optional">optional</span></label>
            <input id="offer-purchase-url" class="<?= e($inputClass) ?>" name="purchase_url" type="url" value="<?= e((string) ($config['purchase_url'] ?? '')) ?>" placeholder="https://">
        </div>
    </div>

    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="service"<?= $type !== 'service' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Service details</h3>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-scope">What's included</label>
            <textarea id="offer-scope" class="<?= e($textareaClass) ?>" name="scope" rows="2" placeholder="e.g. Logo design, 3 revisions, source files"><?= e((string) ($config['scope'] ?? '')) ?></textarea>
        </div>
        <div class="<?= e($gridClass) ?>">
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-turnaround">Turnaround</label>
                <input id="offer-turnaround" class="<?= e($inputClass) ?>" name="turnaround" type="text" value="<?= e((string) ($config['turnaround'] ?? '')) ?>" placeholder="e.g. 5 business days">
            </div>
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-service-area">Service area</label>
                <input id="offer-service-area" class="<?= e($inputClass) ?>" name="service_area" type="text" value="<?= e((string) ($config['service_area'] ?? '')) ?>" placeholder="e.g. Lagos, Remote">
            </div>
        </div>
    </div>

    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="membership"<?= $type !== 'membership' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Membership details</h3>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-registration-url">Registration link <span class="lb-offer-required" aria-hidden="true">*</span></label>
            <input id="offer-registration-url" class="<?= e($inputClass) ?>" name="registration_url" type="url" value="<?= e((string) ($config['registration_url'] ?? '')) ?>" placeholder="https://your-community.com/join" data-offer-required-for="membership" data-offer-step-required>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-benefits">Member benefits</label>
            <textarea id="offer-benefits" class="<?= e($textareaClass) ?>" name="benefits" rows="3" placeholder="Weekly calls, resource library, private group…"><?= e((string) ($config['benefits'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="consultation"<?= $type !== 'consultation' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Consultation &amp; booking</h3>
        </div>
        <div class="<?= e($gridClass) ?>">
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-duration">Session length (minutes)</label>
                <input id="offer-duration" class="<?= e($inputClass) ?>" name="duration_minutes" type="number" min="15" max="480" step="15" value="<?= e((string) ($config['duration_minutes'] ?? '60')) ?>">
            </div>
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-session-format">Format</label>
                <select id="offer-session-format" name="session_format">
                    <?php foreach (['video' => 'Video call', 'phone' => 'Phone', 'in_person' => 'In person'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ((string) ($config['session_format'] ?? 'video')) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-availability">Availability notes</label>
            <input id="offer-availability" class="<?= e($inputClass) ?>" name="availability_notes" type="text" value="<?= e((string) ($config['availability_notes'] ?? '')) ?>" placeholder="e.g. Mon–Fri 9am–5pm WAT">
        </div>
        <fieldset class="lb-offer-booking-mode">
            <legend class="<?= e($labelClass) ?>">How should bookings work?</legend>
            <?php $bookingMode = (string) ($config['booking_mode'] ?? 'leadbird_calendar'); ?>
            <label class="lb-offer-radio-card">
                <input type="radio" name="booking_mode" value="leadbird_calendar" <?= $bookingMode === 'leadbird_calendar' ? 'checked' : '' ?> data-booking-mode-input>
                <span>
                    <strong class="font-heading">Lead Bird + Google Calendar</strong>
                    <span>AI collects details and blocks your calendar automatically.</span>
                </span>
            </label>
            <label class="lb-offer-radio-card">
                <input type="radio" name="booking_mode" value="external_link" <?= $bookingMode === 'external_link' ? 'checked' : '' ?> data-booking-mode-input>
                <span>
                    <strong class="font-heading">External booking link</strong>
                    <span>Send your Calendly or booking page link in DMs.</span>
                </span>
            </label>
        </fieldset>
        <div class="lb-offer-calendar-connect" data-calendar-connect-panel<?= $bookingMode !== 'leadbird_calendar' ? ' hidden' : '' ?>>
            <?php if ($googleCalendarConnected): ?>
                <div class="lb-offer-calendar-status lb-offer-calendar-status--connected" role="status">
                    <span class="lb-offer-calendar-dot" aria-hidden="true"></span>
                    Google Calendar connected.
                </div>
            <?php else: ?>
                <div class="lb-offer-calendar-status" role="status">
                    Connect Google Calendar so the AI can block time and book sessions.
                </div>
                <a href="<?= e($calendarConnectUrl) ?>" class="lb-offer-calendar-btn">Connect Google Calendar</a>
            <?php endif; ?>
        </div>
        <div class="<?= e($fieldClass) ?>" data-external-booking-panel<?= $bookingMode !== 'external_link' ? ' hidden' : '' ?>>
            <label class="<?= e($labelClass) ?>" for="offer-external-booking">Booking link <span class="lb-offer-required" aria-hidden="true">*</span></label>
            <input id="offer-external-booking" class="<?= e($inputClass) ?>" name="external_booking_url" type="url" value="<?= e((string) ($config['external_booking_url'] ?? '')) ?>" placeholder="https://calendly.com/you" data-offer-step-required data-offer-required-when="booking_mode=external_link">
        </div>
    </div>

    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="course"<?= $type !== 'course' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Course details</h3>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-enrollment-url">Enrollment link <span class="lb-offer-required" aria-hidden="true">*</span></label>
            <input id="offer-enrollment-url" class="<?= e($inputClass) ?>" name="enrollment_url" type="url" value="<?= e((string) ($config['enrollment_url'] ?? '')) ?>" placeholder="https://" data-offer-required-for="course" data-offer-step-required>
        </div>
        <div class="<?= e($gridClass) ?>">
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-access-type">Access type</label>
                <select id="offer-access-type" name="access_type">
                    <?php foreach (['lifetime' => 'Lifetime access', 'subscription' => 'Subscription', 'cohort' => 'Cohort / intake'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ((string) ($config['access_type'] ?? 'lifetime')) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-modules">Modules or curriculum</label>
            <textarea id="offer-modules" class="<?= e($textareaClass) ?>" name="modules" rows="3" placeholder="Module 1: Foundations, Module 2: …"><?= e((string) ($config['modules'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="<?= e($blockClass) ?> lb-offer-type-panel" data-offer-panel="event"<?= $type !== 'event' ? ' hidden' : '' ?>>
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Event details</h3>
        </div>
        <div class="<?= e($gridClass) ?>">
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-event-date">Date &amp; time</label>
                <input id="offer-event-date" class="<?= e($inputClass) ?>" name="event_date" type="text" value="<?= e((string) ($config['event_date'] ?? '')) ?>" placeholder="e.g. Sat 15 Aug, 2pm WAT">
            </div>
            <div class="<?= e($fieldClass) ?>">
                <label class="<?= e($labelClass) ?>" for="offer-location">Location</label>
                <input id="offer-location" class="<?= e($inputClass) ?>" name="location" type="text" value="<?= e((string) ($config['location'] ?? '')) ?>" placeholder="Venue or Online">
            </div>
        </div>
        <div class="<?= e($fieldClass) ?>">
            <label class="<?= e($labelClass) ?>" for="offer-ticket-url">Ticket / registration link <span class="lb-offer-required" aria-hidden="true">*</span></label>
            <input id="offer-ticket-url" class="<?= e($inputClass) ?>" name="ticket_url" type="url" value="<?= e((string) ($config['ticket_url'] ?? '')) ?>" placeholder="https://" data-offer-required-for="event" data-offer-step-required>
        </div>
    </div>
</div>

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds comprehensive tracking columns for CRM lead attribution:
     * - UTM parameters for marketing attribution
     * - Traffic/session data for analytics
     * - Device/geo info for segmentation
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // UTM Attribution Parameters
            $table->string('utm_source', 255)->nullable()->after('source')
                ->comment('Traffic source: google, facebook, linkedin, etc.');
            $table->string('utm_medium', 255)->nullable()->after('utm_source')
                ->comment('Traffic medium: cpc, organic, email, social, referral');
            $table->string('utm_campaign', 255)->nullable()->after('utm_medium')
                ->comment('Campaign name for ROI tracking');
            $table->string('utm_content', 255)->nullable()->after('utm_campaign')
                ->comment('Ad content/variation for A/B testing');
            $table->string('utm_term', 255)->nullable()->after('utm_content')
                ->comment('Paid search keywords');

            // Click IDs for ad platform attribution
            $table->string('gclid', 255)->nullable()->after('utm_term')
                ->comment('Google Ads click ID');
            $table->string('fbclid', 255)->nullable()->after('gclid')
                ->comment('Facebook/Meta click ID');
            $table->string('msclkid', 255)->nullable()->after('fbclid')
                ->comment('Microsoft Ads click ID');

            // Traffic/Session Data
            $table->string('ip_address', 45)->nullable()->after('msclkid')
                ->comment('Visitor IP address (supports IPv6)');
            $table->text('user_agent')->nullable()->after('ip_address')
                ->comment('Browser user agent string');
            $table->text('referrer_url')->nullable()->after('user_agent')
                ->comment('HTTP referrer - where they came from');
            $table->string('landing_page', 500)->nullable()->after('referrer_url')
                ->comment('First page visited in session');
            $table->string('entry_page', 500)->nullable()->after('landing_page')
                ->comment('Page where form was submitted');

            // Device Information
            $table->string('device_type', 50)->nullable()->after('entry_page')
                ->comment('mobile, desktop, tablet');
            $table->string('browser', 100)->nullable()->after('device_type')
                ->comment('Chrome, Safari, Firefox, Edge');
            $table->string('operating_system', 100)->nullable()->after('browser')
                ->comment('Windows, macOS, iOS, Android');

            // Geo Data (from IP lookup)
            $table->string('geo_country', 100)->nullable()->after('operating_system')
                ->comment('Country from IP geolocation');
            $table->string('geo_region', 100)->nullable()->after('geo_country')
                ->comment('State/Region from IP geolocation');
            $table->string('geo_city', 100)->nullable()->after('geo_region')
                ->comment('City from IP geolocation');
            $table->string('geo_timezone', 100)->nullable()->after('geo_city')
                ->comment('Timezone from IP geolocation');

            // Session/Attribution Data
            $table->string('session_id', 100)->nullable()->after('geo_timezone')
                ->comment('Session identifier for journey tracking');
            $table->integer('visit_count')->nullable()->after('session_id')
                ->comment('Number of visits before conversion');
            $table->integer('pages_viewed')->nullable()->after('visit_count')
                ->comment('Pages viewed in session');
            $table->integer('time_on_site_seconds')->nullable()->after('pages_viewed')
                ->comment('Time on site before form submission');
            $table->timestamp('first_visit_at')->nullable()->after('time_on_site_seconds')
                ->comment('First ever visit timestamp');

            // First/Last Touch Attribution
            $table->string('first_touch_source', 255)->nullable()->after('first_visit_at')
                ->comment('Original discovery channel');
            $table->string('first_touch_medium', 255)->nullable()->after('first_touch_source')
                ->comment('Original traffic medium');
            $table->string('first_touch_campaign', 255)->nullable()->after('first_touch_medium')
                ->comment('Original campaign');
            $table->string('last_touch_source', 255)->nullable()->after('first_touch_campaign')
                ->comment('Final conversion trigger source');
            $table->string('last_touch_medium', 255)->nullable()->after('last_touch_source')
                ->comment('Final conversion trigger medium');
            $table->string('last_touch_campaign', 255)->nullable()->after('last_touch_medium')
                ->comment('Final conversion campaign');

            // Add indexes for common queries
            $table->index('utm_source');
            $table->index('utm_medium');
            $table->index('utm_campaign');
            $table->index('device_type');
            $table->index('geo_country');
            $table->index('geo_region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['utm_medium']);
            $table->dropIndex(['utm_campaign']);
            $table->dropIndex(['device_type']);
            $table->dropIndex(['geo_country']);
            $table->dropIndex(['geo_region']);

            // Drop columns
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                'gclid', 'fbclid', 'msclkid',
                'ip_address', 'user_agent', 'referrer_url', 'landing_page', 'entry_page',
                'device_type', 'browser', 'operating_system',
                'geo_country', 'geo_region', 'geo_city', 'geo_timezone',
                'session_id', 'visit_count', 'pages_viewed', 'time_on_site_seconds', 'first_visit_at',
                'first_touch_source', 'first_touch_medium', 'first_touch_campaign',
                'last_touch_source', 'last_touch_medium', 'last_touch_campaign',
            ]);
        });
    }
};

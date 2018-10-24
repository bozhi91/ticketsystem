<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create locale table
		Schema::create('locales', function (Blueprint $table) {
			$table->increments('id');
			$table->char('locale', 2)->index()->unique();
			$table->string('flag');
			$table->char('dir', 3)->default('ltr');
			$table->string('name');
			$table->string('script');
			$table->string('native');
			$table->string('regional');
			$table->timestamps();
		});

		// Add default languages
		$languages = [
            [
                'locale' => 'en',
                'name' => 'Inglés',
                'script' => 'Latn',
                'native' => 'English',
                'regional' => 'en_GB'
            ],
            [
    			'locale' => 'es',
    			'name' => 'Español',
    			'script' => 'Latn',
    			'native' => 'Español',
    			'regional' => 'es_ES',
            ],
            [
                'locale' => 'ca',
                'name' => 'Catalán',
                'script' => 'Latn',
                'native' => 'Català',
                'regional' => 'ca_ES'
            ],
            [
                'locale' => 'de',
                'name' => 'Alemán',
                'script' => 'Latn',
                'native' => 'Deutsch',
                'regional' => 'de_DE'
            ],
			[
                'locale' => 'fr',
                'name' => 'Francés',
                'script' => 'Latn',
                'native' => 'Français',
                'regional' => 'fr_FR'
            ],
            [
                'locale' => 'it',
                'name' => 'Italiano',
                'script' => 'Latn',
                'native' => 'Italiano',
                'regional' => 'it_IT'
            ],
            [
                'locale' => 'nl',
                'name' => 'Holandés',
                'script' => 'Latn',
                'native' => 'Nederlands',
                'regional' => 'nl_NL'
            ],
            [
                'locale' => 'pt',
                'name' => 'Portugué',
                'script' => 'Latn',
                'native' => 'português',
                'regional' => 'pt_PT'
            ],
            [
                'locale' => 'ru',
                'name' => 'Ruso',
                'script' => 'Cyrl',
                'native' => 'русский',
                'regional' => 'ru_RU'
            ],
            [
                'locale' => 'tk',
                'name' => 'Turco',
                'script' => 'Latn',
                'native' => 'Türkçe',
                'regional' => 'tr_TR'
            ],
        ];

        foreach ($languages as $lang)
        {
            \DB::table('locales')->insert([
                'locale' => $lang['locale'],
                'name' => $lang['name'],
                'script' => $lang['script'],
                'native' => $lang['native'],
                'regional' => $lang['regional'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('locales');
    }
}

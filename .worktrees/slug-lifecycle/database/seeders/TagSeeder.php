<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::data() as $tag) {
            Tag::query()->forceCreate($tag);
        }
    }

    /** @return list<array{id: int, name: string, slug: string}> */
    public static function data(): array
    {
        return [
            ['id' => 1, 'name' => 'Feito a Mao', 'slug' => 'feito-a-mao'],
            ['id' => 2, 'name' => 'Edicao Limitada', 'slug' => 'edicao-limitada'],
            ['id' => 3, 'name' => 'Sustentavel', 'slug' => 'sustentavel'],
            ['id' => 4, 'name' => 'Ceramica', 'slug' => 'ceramica'],
            ['id' => 5, 'name' => 'Madeira', 'slug' => 'madeira'],
            ['id' => 6, 'name' => 'Couro', 'slug' => 'couro'],
            ['id' => 7, 'name' => 'Macrame', 'slug' => 'macrame'],
            ['id' => 8, 'name' => 'Bordado', 'slug' => 'bordado'],
            ['id' => 9, 'name' => 'Aquarela', 'slug' => 'aquarela'],
            ['id' => 10, 'name' => 'Papel Reciclado', 'slug' => 'papel-reciclado'],
            ['id' => 11, 'name' => 'Argila', 'slug' => 'argila'],
            ['id' => 12, 'name' => 'Vidro Soprado', 'slug' => 'vidro-soprado'],
            ['id' => 13, 'name' => 'Laca', 'slug' => 'laca'],
            ['id' => 14, 'name' => 'Rattan', 'slug' => 'rattan'],
            ['id' => 15, 'name' => 'Resina', 'slug' => 'resina'],
            ['id' => 16, 'name' => 'Oleo Essencial', 'slug' => 'oleo-essencial'],
            ['id' => 17, 'name' => 'Cera de Soja', 'slug' => 'cera-de-soja'],
            ['id' => 18, 'name' => 'Algodao Organico', 'slug' => 'algodao-organico'],
            ['id' => 19, 'name' => 'Linho', 'slug' => 'linho'],
            ['id' => 20, 'name' => 'Prata 925', 'slug' => 'prata-925'],
            ['id' => 21, 'name' => 'Banhado a Ouro', 'slug' => 'banhado-a-ouro'],
            ['id' => 22, 'name' => 'Pedra Natural', 'slug' => 'pedra-natural'],
            ['id' => 23, 'name' => 'Cristal', 'slug' => 'cristal'],
            ['id' => 24, 'name' => 'Tie-Dye', 'slug' => 'tie-dye'],
            ['id' => 25, 'name' => 'Shibori', 'slug' => 'shibori'],
            ['id' => 26, 'name' => 'Wabi-Sabi', 'slug' => 'wabi-sabi'],
            ['id' => 27, 'name' => 'Kintsugi', 'slug' => 'kintsugi'],
            ['id' => 28, 'name' => 'Japones', 'slug' => 'japones'],
            ['id' => 29, 'name' => 'Boho', 'slug' => 'boho'],
            ['id' => 30, 'name' => 'Minimalista', 'slug' => 'minimalista'],
        ];
    }
}

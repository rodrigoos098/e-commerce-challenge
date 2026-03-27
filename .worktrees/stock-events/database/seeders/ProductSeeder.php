<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::products() as $product) {
            Product::query()->forceCreate($product);
        }

        DB::table('product_tag')->insert(self::productTags());

        $this->publishImages();
    }

    private function publishImages(): void
    {
        $source = database_path('seeders/images/products');
        $dest = Storage::disk('public')->path('products');

        if (! File::isDirectory($dest)) {
            File::makeDirectory($dest, 0755, true);
        }

        foreach (File::files($source) as $file) {
            File::copy($file->getPathname(), $dest . DIRECTORY_SEPARATOR . $file->getFilename());
        }
    }

    /** @return list<array<string, mixed>> */
    public static function products(): array
    {
        return [
            ['id' => 1, 'name' => 'Vaso Wabi-Sabi em Ceramica Crua', 'slug' => 'vaso-wabi-sabi-em-ceramica-crua', 'description' => 'Vaso modelado a mao com textura organica e acabamento natural. Cada peca e unica.', 'price' => '139.00', 'cost_price' => '48.65', 'quantity' => 19, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/1.webp', 'category_id' => 2],
            ['id' => 2, 'name' => 'Tigela Kintsugi Dourada', 'slug' => 'tigela-kintsugi-dourada', 'description' => 'Tigela em ceramica com reparo decorativo em ouro, celebrando a beleza das imperfeicoes.', 'price' => '189.00', 'cost_price' => '56.70', 'quantity' => 9, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/2.webp', 'category_id' => 3],
            ['id' => 3, 'name' => 'Caneca Artesanal Raku', 'slug' => 'caneca-artesanal-raku', 'description' => 'Caneca feita com tecnica Raku, com esmalte unico criado pelo fogo.', 'price' => '94.00', 'cost_price' => '28.20', 'quantity' => 27, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/3.webp', 'category_id' => 4],
            ['id' => 4, 'name' => 'Almofada Shibori Indigo', 'slug' => 'almofada-shibori-indigo', 'description' => 'Almofada tingida a mao com tecnica japonesa Shibori em tons de indigo.', 'price' => '209.00', 'cost_price' => '73.15', 'quantity' => 24, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/4.webp', 'category_id' => 7],
            ['id' => 5, 'name' => 'Tapete Macrame Natural', 'slug' => 'tapete-macrame-natural', 'description' => 'Tapete artesanal em macrame de algodao cru, ideal para ambientes aconchegantes.', 'price' => '299.00', 'cost_price' => '104.65', 'quantity' => 17, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/5.webp', 'category_id' => 8],
            ['id' => 6, 'name' => 'Print Botanico Aquarela A3', 'slug' => 'print-botanico-aquarela-a3', 'description' => 'Ilustracao botanica original em aquarela, impressa em papel 300g com certificado.', 'price' => '94.00', 'cost_price' => '23.50', 'quantity' => 49, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/6.webp', 'category_id' => 12],
            ['id' => 7, 'name' => 'Caderno Costura Japonesa', 'slug' => 'caderno-costura-japonesa', 'description' => 'Caderno encadernado a mao com costura japonesa Stab Binding e capa de papel artesanal.', 'price' => '74.00', 'cost_price' => '22.20', 'quantity' => 43, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/7.webp', 'category_id' => 17],
            ['id' => 8, 'name' => 'Brinco Gota Resina e Ouro', 'slug' => 'brinco-gota-resina-e-ouro', 'description' => 'Brincos em resina translucida com flocos de ouro, feitos a mao.', 'price' => '119.00', 'cost_price' => '29.75', 'quantity' => 34, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/8.webp', 'category_id' => 22],
            ['id' => 9, 'name' => 'Colar Pedra Natural Quartzo Rosa', 'slug' => 'colar-pedra-natural-quartzo-rosa', 'description' => 'Colar com pingente de quartzo rosa e corrente banhada a ouro 18k.', 'price' => '174.00', 'cost_price' => '52.20', 'quantity' => 16, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/9.webp', 'category_id' => 23],
            ['id' => 10, 'name' => 'Vela de Soja Hinoki e Cedro', 'slug' => 'vela-de-soja-hinoki-e-cedro', 'description' => 'Vela artesanal de cera de soja com essencia de madeira japonesa hinoki.', 'price' => '89.00', 'cost_price' => '22.25', 'quantity' => 34, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/10.webp', 'category_id' => 27],
            ['id' => 11, 'name' => 'Difusor Bambu e Flor de Cerejeira', 'slug' => 'difusor-bambu-e-flor-de-cerejeira', 'description' => 'Difusor em frasco de vidro com varetas de bambu e essencia floral.', 'price' => '129.00', 'cost_price' => '38.70', 'quantity' => 30, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/11.webp', 'category_id' => 29],
            ['id' => 12, 'name' => 'Bolsa Tote Linho Natural', 'slug' => 'bolsa-tote-linho-natural', 'description' => 'Bolsa tote em linho organico com detalhes em couro natural.', 'price' => '189.00', 'cost_price' => '66.15', 'quantity' => 10, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/12.webp', 'category_id' => 9],
            ['id' => 13, 'name' => 'Prato Raso Ceramica Terracota', 'slug' => 'prato-raso-ceramica-terracota', 'description' => 'Prato artesanal em terracota com esmalte irregular e borda organica.', 'price' => '134.00', 'cost_price' => '40.20', 'quantity' => 24, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/13.webp', 'category_id' => 3],
            ['id' => 14, 'name' => 'Manta Trico Chunky Merino', 'slug' => 'manta-trico-chunky-merino', 'description' => 'Manta feita a mao em trico grosso com la merino pura.', 'price' => '449.00', 'cost_price' => '179.60', 'quantity' => 10, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/14.webp', 'category_id' => 7],
            ['id' => 15, 'name' => 'Aquarela Original Mar Japones', 'slug' => 'aquarela-original-mar-japones', 'description' => 'Pintura original em aquarela sobre papel Arches 300g, assinada pelo artista.', 'price' => '424.00', 'cost_price' => '106.00', 'quantity' => 3, 'min_quantity' => 2, 'active' => true, 'image_url' => '/storage/products/15.webp', 'category_id' => 13],
            ['id' => 16, 'name' => 'Porta-Incenso Ceramica Montanha', 'slug' => 'porta-incenso-ceramica-montanha', 'description' => 'Porta-incenso em formato de montanha, modelado a mao em argila.', 'price' => '74.00', 'cost_price' => '18.50', 'quantity' => 38, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/16.webp', 'category_id' => 28],
            ['id' => 17, 'name' => 'Pulseira Couro Trancado', 'slug' => 'pulseira-couro-trancado', 'description' => 'Pulseira em couro natural trancado a mao com fecho magnetico.', 'price' => '104.00', 'cost_price' => '26.00', 'quantity' => 26, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/17.webp', 'category_id' => 24],
            ['id' => 18, 'name' => 'Kokedama Samambaia', 'slug' => 'kokedama-samambaia', 'description' => 'Bola de musgo com samambaia viva, tecnica japonesa milenar.', 'price' => '114.00', 'cost_price' => '34.20', 'quantity' => 20, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/18.webp', 'category_id' => 38],
            ['id' => 19, 'name' => 'Organizador Mesa Madeira', 'slug' => 'organizador-mesa-madeira', 'description' => 'Organizador de mesa em madeira macica com compartimentos para papelaria.', 'price' => '149.00', 'cost_price' => '52.15', 'quantity' => 21, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/19.webp', 'category_id' => 33],
            ['id' => 20, 'name' => 'Quadro Moldura Madeira Flutuante', 'slug' => 'quadro-moldura-madeira-flutuante', 'description' => 'Moldura artesanal em madeira clara com efeito flutuante para prints ate A3.', 'price' => '184.00', 'cost_price' => '64.40', 'quantity' => 20, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/20.webp', 'category_id' => 15],
            ['id' => 21, 'name' => 'Cartoes Letterpress Botanicos', 'slug' => 'cartoes-letterpress-botanicos', 'description' => 'Kit de 10 cartoes em letterpress com ilustracoes botanicas e envelopes kraft.', 'price' => '59.00', 'cost_price' => '14.75', 'quantity' => 50, 'min_quantity' => 12, 'active' => true, 'image_url' => '/storage/products/21.webp', 'category_id' => 18],
            ['id' => 22, 'name' => 'Broche Ceramica Passaro', 'slug' => 'broche-ceramica-passaro', 'description' => 'Broche em ceramica pintada a mao com forma de passaro e acabamento dourado.', 'price' => '59.00', 'cost_price' => '14.75', 'quantity' => 40, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/22.webp', 'category_id' => 25],
            ['id' => 23, 'name' => 'Washi Tape Floral Japones', 'slug' => 'washi-tape-floral-japones', 'description' => 'Kit de 5 rolos de washi tape com estampas florais japonesas.', 'price' => '29.00', 'cost_price' => '8.70', 'quantity' => 77, 'min_quantity' => 15, 'active' => true, 'image_url' => '/storage/products/23.webp', 'category_id' => 20],
            ['id' => 24, 'name' => 'Escultura Abstrata Ceramica', 'slug' => 'escultura-abstrata-ceramica', 'description' => 'Escultura decorativa em ceramica com formas organicas e acabamento matte.', 'price' => '324.00', 'cost_price' => '97.20', 'quantity' => 8, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/24.webp', 'category_id' => 5],
            ['id' => 25, 'name' => 'Terrario Geometrico Vidro', 'slug' => 'terrario-geometrico-vidro', 'description' => 'Terrario em vidro geometrico com suculentas e pedras decorativas.', 'price' => '189.00', 'cost_price' => '66.15', 'quantity' => 12, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/25.webp', 'category_id' => 38],
            ['id' => 26, 'name' => 'Tigela Kintsugi Dourada M', 'slug' => 'tigela-kintsugi-dourada-m', 'description' => 'Tigela em ceramica com reparo decorativo em ouro, celebrando a beleza das imperfeicoes. Pensado para quem valoriza o feito a mao, a imperfeicao e a autenticidade.', 'price' => '208.60', 'cost_price' => '83.44', 'quantity' => 4, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/26.webp', 'category_id' => 34],
            ['id' => 27, 'name' => 'Almofada Shibori Indigo M', 'slug' => 'almofada-shibori-indigo-m', 'description' => 'Almofada tingida a mao com tecnica japonesa Shibori em tons de indigo. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '162.64', 'cost_price' => '81.32', 'quantity' => 20, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/27.webp', 'category_id' => 10],
            ['id' => 28, 'name' => 'Quadro Moldura Madeira Flutuante Azul Profundo', 'slug' => 'quadro-moldura-madeira-flutuante-azul-profundo', 'description' => 'Moldura artesanal em madeira clara com efeito flutuante para prints ate A3. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '162.52', 'cost_price' => '78.01', 'quantity' => 12, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/28.webp', 'category_id' => 17],
            ['id' => 29, 'name' => 'Cartoes Letterpress Botanicos Natural', 'slug' => 'cartoes-letterpress-botanicos-natural', 'description' => 'Kit de 10 cartoes em letterpress com ilustracoes botanicas e envelopes kraft. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '39.30', 'cost_price' => '15.72', 'quantity' => 19, 'min_quantity' => 11, 'active' => true, 'image_url' => '/storage/products/29.webp', 'category_id' => 2],
            ['id' => 30, 'name' => 'Prato Raso Ceramica Terracota M', 'slug' => 'prato-raso-ceramica-terracota-m', 'description' => 'Prato artesanal em terracota com esmalte irregular e borda organica. Pensado para quem valoriza o feito a mao, a imperfeicao e a autenticidade.', 'price' => '172.12', 'cost_price' => '56.80', 'quantity' => 4, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/30.webp', 'category_id' => 38],
            ['id' => 31, 'name' => 'Terrario Geometrico Vidro Branco Cru', 'slug' => 'terrario-geometrico-vidro-branco-cru', 'description' => 'Terrario em vidro geometrico com suculentas e pedras decorativas. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '159.47', 'cost_price' => '70.17', 'quantity' => 17, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/31.webp', 'category_id' => 4],
            ['id' => 32, 'name' => 'Caderno Costura Japonesa A4', 'slug' => 'caderno-costura-japonesa-a4', 'description' => 'Caderno encadernado a mao com costura japonesa Stab Binding e capa de papel artesanal. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '91.14', 'cost_price' => '30.08', 'quantity' => 25, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/32.webp', 'category_id' => 17],
            ['id' => 33, 'name' => 'Colar Pedra Natural Quartzo Rosa Dourado', 'slug' => 'colar-pedra-natural-quartzo-rosa-dourado', 'description' => 'Colar com pingente de quartzo rosa e corrente banhada a ouro 18k. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '183.58', 'cost_price' => '80.78', 'quantity' => 12, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/33.webp', 'category_id' => 36],
            ['id' => 34, 'name' => 'Difusor Bambu e Flor de Cerejeira P', 'slug' => 'difusor-bambu-e-flor-de-cerejeira-p', 'description' => 'Difusor em frasco de vidro com varetas de bambu e essencia floral. Pensado para quem valoriza o feito a mao, a imperfeicao e a autenticidade.', 'price' => '144.96', 'cost_price' => '44.94', 'quantity' => 19, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/34.webp', 'category_id' => 34],
            ['id' => 35, 'name' => 'Quadro Moldura Madeira Flutuante Branco Cru', 'slug' => 'quadro-moldura-madeira-flutuante-branco-cru', 'description' => 'Moldura artesanal em madeira clara com efeito flutuante para prints ate A3. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '213.97', 'cost_price' => '83.45', 'quantity' => 36, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/35.webp', 'category_id' => 37],
            ['id' => 36, 'name' => 'Tapete Macrame Natural M', 'slug' => 'tapete-macrame-natural-m', 'description' => 'Tapete artesanal em macrame de algodao cru, ideal para ambientes aconchegantes. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '251.01', 'cost_price' => '105.42', 'quantity' => 7, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/36.webp', 'category_id' => 22],
            ['id' => 37, 'name' => 'Print Botanico Aquarela A3 A4', 'slug' => 'print-botanico-aquarela-a3-a4', 'description' => 'Ilustracao botanica original em aquarela, impressa em papel 300g com certificado. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '110.44', 'cost_price' => '33.13', 'quantity' => 49, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/37.webp', 'category_id' => 14],
            ['id' => 38, 'name' => 'Caderno Costura Japonesa Natural', 'slug' => 'caderno-costura-japonesa-natural', 'description' => 'Caderno encadernado a mao com costura japonesa Stab Binding e capa de papel artesanal. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '79.06', 'cost_price' => '33.21', 'quantity' => 25, 'min_quantity' => 8, 'active' => true, 'image_url' => '/storage/products/38.webp', 'category_id' => 23],
            ['id' => 39, 'name' => 'Kokedama Samambaia Natural', 'slug' => 'kokedama-samambaia-natural', 'description' => 'Bola de musgo com samambaia viva, tecnica japonesa milenar. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '90.43', 'cost_price' => '33.46', 'quantity' => 6, 'min_quantity' => 5, 'active' => true, 'image_url' => '/storage/products/39.webp', 'category_id' => 12],
            ['id' => 40, 'name' => 'Quadro Moldura Madeira Flutuante Branco', 'slug' => 'quadro-moldura-madeira-flutuante-branco', 'description' => 'Moldura artesanal em madeira clara com efeito flutuante para prints ate A3. Pensado para quem valoriza o feito a mao, a imperfeicao e a autenticidade.', 'price' => '211.66', 'cost_price' => '80.43', 'quantity' => 11, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/40.webp', 'category_id' => 9],
            ['id' => 41, 'name' => 'Vela de Soja Hinoki e Cedro 300ml', 'slug' => 'vela-de-soja-hinoki-e-cedro-300ml', 'description' => 'Vela artesanal de cera de soja com essencia de madeira japonesa hinoki. Combina tecnicas tradicionais com design contemporaneo e sustentavel.', 'price' => '68.28', 'cost_price' => '26.63', 'quantity' => 16, 'min_quantity' => 6, 'active' => true, 'image_url' => '/storage/products/41.webp', 'category_id' => 14],
            ['id' => 42, 'name' => 'Kokedama Samambaia Terracota', 'slug' => 'kokedama-samambaia-terracota', 'description' => 'Bola de musgo com samambaia viva, tecnica japonesa milenar. Combina tecnicas tradicionais com design contemporaneo e sustentavel.', 'price' => '100.11', 'cost_price' => '32.04', 'quantity' => 16, 'min_quantity' => 3, 'active' => true, 'image_url' => '/storage/products/42.webp', 'category_id' => 25],
            ['id' => 43, 'name' => 'Manta Trico Chunky Merino P', 'slug' => 'manta-trico-chunky-merino-p', 'description' => 'Manta feita a mao em trico grosso com la merino pura. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '509.01', 'cost_price' => '234.14', 'quantity' => 4, 'min_quantity' => 1, 'active' => true, 'image_url' => '/storage/products/43.webp', 'category_id' => 31],
            ['id' => 44, 'name' => 'Manta Trico Chunky Merino Natural', 'slug' => 'manta-trico-chunky-merino-natural', 'description' => 'Manta feita a mao em trico grosso com la merino pura. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '368.26', 'cost_price' => '176.76', 'quantity' => 7, 'min_quantity' => 1, 'active' => true, 'image_url' => '/storage/products/44.webp', 'category_id' => 27],
            ['id' => 45, 'name' => 'Prato Raso Ceramica Terracota P', 'slug' => 'prato-raso-ceramica-terracota-p', 'description' => 'Prato artesanal em terracota com esmalte irregular e borda organica. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '92.15', 'cost_price' => '27.65', 'quantity' => 24, 'min_quantity' => 4, 'active' => true, 'image_url' => '/storage/products/45.webp', 'category_id' => 7],
            ['id' => 46, 'name' => 'Difusor Bambu e Flor de Cerejeira 300ml', 'slug' => 'difusor-bambu-e-flor-de-cerejeira-300ml', 'description' => 'Difusor em frasco de vidro com varetas de bambu e essencia floral. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '145.46', 'cost_price' => '48.00', 'quantity' => 0, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/46.webp', 'category_id' => 10],
            ['id' => 47, 'name' => 'Manta Trico Chunky Merino G', 'slug' => 'manta-trico-chunky-merino-g', 'description' => 'Manta feita a mao em trico grosso com la merino pura. Combina tecnicas tradicionais com design contemporaneo e sustentavel.', 'price' => '574.54', 'cost_price' => '235.56', 'quantity' => 2, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/47.webp', 'category_id' => 8],
            ['id' => 48, 'name' => 'Organizador Mesa Madeira Azul Profundo', 'slug' => 'organizador-mesa-madeira-azul-profundo', 'description' => 'Organizador de mesa em madeira macica com compartimentos para papelaria. Combina tecnicas tradicionais com design contemporaneo e sustentavel.', 'price' => '150.97', 'cost_price' => '72.47', 'quantity' => 0, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/48.webp', 'category_id' => 3],
            ['id' => 49, 'name' => 'Difusor Bambu e Flor de Cerejeira Natural', 'slug' => 'difusor-bambu-e-flor-de-cerejeira-natural', 'description' => 'Difusor em frasco de vidro com varetas de bambu e essencia floral. Ideal para presente ou para decorar ambientes com personalidade e aconchego.', 'price' => '130.45', 'cost_price' => '48.27', 'quantity' => 2, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/49.webp', 'category_id' => 38],
            ['id' => 50, 'name' => 'Quadro Moldura Madeira Flutuante Natural', 'slug' => 'quadro-moldura-madeira-flutuante-natural', 'description' => 'Moldura artesanal em madeira clara com efeito flutuante para prints ate A3. Peca unica feita a mao com materiais naturais e acabamento artesanal.', 'price' => '171.61', 'cost_price' => '78.94', 'quantity' => 0, 'min_quantity' => 10, 'active' => true, 'image_url' => '/storage/products/50.webp', 'category_id' => 14],
        ];
    }

    /** @return list<array{product_id: int, tag_id: int}> */
    public static function productTags(): array
    {
        return [
            ['product_id' => 1, 'tag_id' => 1],
            ['product_id' => 1, 'tag_id' => 4],
            ['product_id' => 1, 'tag_id' => 26],
            ['product_id' => 1, 'tag_id' => 28],
            ['product_id' => 2, 'tag_id' => 4],
            ['product_id' => 2, 'tag_id' => 21],
            ['product_id' => 2, 'tag_id' => 27],
            ['product_id' => 2, 'tag_id' => 28],
            ['product_id' => 3, 'tag_id' => 1],
            ['product_id' => 3, 'tag_id' => 4],
            ['product_id' => 3, 'tag_id' => 28],
            ['product_id' => 4, 'tag_id' => 1],
            ['product_id' => 4, 'tag_id' => 18],
            ['product_id' => 4, 'tag_id' => 25],
            ['product_id' => 4, 'tag_id' => 28],
            ['product_id' => 5, 'tag_id' => 1],
            ['product_id' => 5, 'tag_id' => 7],
            ['product_id' => 5, 'tag_id' => 18],
            ['product_id' => 5, 'tag_id' => 29],
            ['product_id' => 6, 'tag_id' => 9],
            ['product_id' => 6, 'tag_id' => 10],
            ['product_id' => 6, 'tag_id' => 30],
            ['product_id' => 7, 'tag_id' => 1],
            ['product_id' => 7, 'tag_id' => 10],
            ['product_id' => 7, 'tag_id' => 28],
            ['product_id' => 8, 'tag_id' => 1],
            ['product_id' => 8, 'tag_id' => 15],
            ['product_id' => 8, 'tag_id' => 21],
            ['product_id' => 9, 'tag_id' => 21],
            ['product_id' => 9, 'tag_id' => 22],
            ['product_id' => 9, 'tag_id' => 23],
            ['product_id' => 10, 'tag_id' => 3],
            ['product_id' => 10, 'tag_id' => 16],
            ['product_id' => 10, 'tag_id' => 17],
            ['product_id' => 10, 'tag_id' => 28],
            ['product_id' => 11, 'tag_id' => 12],
            ['product_id' => 11, 'tag_id' => 16],
            ['product_id' => 11, 'tag_id' => 28],
            ['product_id' => 12, 'tag_id' => 1],
            ['product_id' => 12, 'tag_id' => 3],
            ['product_id' => 12, 'tag_id' => 6],
            ['product_id' => 12, 'tag_id' => 19],
            ['product_id' => 13, 'tag_id' => 1],
            ['product_id' => 13, 'tag_id' => 4],
            ['product_id' => 13, 'tag_id' => 11],
            ['product_id' => 13, 'tag_id' => 26],
            ['product_id' => 14, 'tag_id' => 1],
            ['product_id' => 14, 'tag_id' => 2],
            ['product_id' => 14, 'tag_id' => 29],
            ['product_id' => 15, 'tag_id' => 2],
            ['product_id' => 15, 'tag_id' => 9],
            ['product_id' => 15, 'tag_id' => 28],
            ['product_id' => 16, 'tag_id' => 1],
            ['product_id' => 16, 'tag_id' => 4],
            ['product_id' => 16, 'tag_id' => 11],
            ['product_id' => 16, 'tag_id' => 28],
            ['product_id' => 17, 'tag_id' => 1],
            ['product_id' => 17, 'tag_id' => 6],
            ['product_id' => 17, 'tag_id' => 30],
            ['product_id' => 18, 'tag_id' => 1],
            ['product_id' => 18, 'tag_id' => 3],
            ['product_id' => 18, 'tag_id' => 28],
            ['product_id' => 19, 'tag_id' => 1],
            ['product_id' => 19, 'tag_id' => 5],
            ['product_id' => 19, 'tag_id' => 30],
            ['product_id' => 20, 'tag_id' => 5],
            ['product_id' => 20, 'tag_id' => 30],
            ['product_id' => 21, 'tag_id' => 1],
            ['product_id' => 21, 'tag_id' => 3],
            ['product_id' => 21, 'tag_id' => 10],
            ['product_id' => 22, 'tag_id' => 1],
            ['product_id' => 22, 'tag_id' => 4],
            ['product_id' => 22, 'tag_id' => 21],
            ['product_id' => 23, 'tag_id' => 10],
            ['product_id' => 23, 'tag_id' => 28],
            ['product_id' => 24, 'tag_id' => 2],
            ['product_id' => 24, 'tag_id' => 4],
            ['product_id' => 24, 'tag_id' => 11],
            ['product_id' => 24, 'tag_id' => 26],
            ['product_id' => 25, 'tag_id' => 3],
            ['product_id' => 25, 'tag_id' => 12],
            ['product_id' => 25, 'tag_id' => 30],
            ['product_id' => 26, 'tag_id' => 9],
            ['product_id' => 26, 'tag_id' => 15],
            ['product_id' => 26, 'tag_id' => 24],
            ['product_id' => 26, 'tag_id' => 30],
            ['product_id' => 27, 'tag_id' => 8],
            ['product_id' => 27, 'tag_id' => 23],
            ['product_id' => 27, 'tag_id' => 25],
            ['product_id' => 28, 'tag_id' => 14],
            ['product_id' => 28, 'tag_id' => 15],
            ['product_id' => 29, 'tag_id' => 6],
            ['product_id' => 29, 'tag_id' => 12],
            ['product_id' => 29, 'tag_id' => 26],
            ['product_id' => 30, 'tag_id' => 2],
            ['product_id' => 30, 'tag_id' => 11],
            ['product_id' => 30, 'tag_id' => 22],
            ['product_id' => 31, 'tag_id' => 10],
            ['product_id' => 31, 'tag_id' => 25],
            ['product_id' => 32, 'tag_id' => 7],
            ['product_id' => 32, 'tag_id' => 22],
            ['product_id' => 32, 'tag_id' => 27],
            ['product_id' => 33, 'tag_id' => 2],
            ['product_id' => 33, 'tag_id' => 11],
            ['product_id' => 34, 'tag_id' => 12],
            ['product_id' => 34, 'tag_id' => 22],
            ['product_id' => 34, 'tag_id' => 27],
            ['product_id' => 35, 'tag_id' => 1],
            ['product_id' => 35, 'tag_id' => 4],
            ['product_id' => 35, 'tag_id' => 15],
            ['product_id' => 35, 'tag_id' => 20],
            ['product_id' => 36, 'tag_id' => 1],
            ['product_id' => 36, 'tag_id' => 28],
            ['product_id' => 37, 'tag_id' => 5],
            ['product_id' => 37, 'tag_id' => 20],
            ['product_id' => 38, 'tag_id' => 15],
            ['product_id' => 38, 'tag_id' => 30],
            ['product_id' => 39, 'tag_id' => 24],
            ['product_id' => 39, 'tag_id' => 28],
            ['product_id' => 40, 'tag_id' => 11],
            ['product_id' => 40, 'tag_id' => 26],
            ['product_id' => 40, 'tag_id' => 27],
            ['product_id' => 41, 'tag_id' => 1],
            ['product_id' => 41, 'tag_id' => 9],
            ['product_id' => 41, 'tag_id' => 10],
            ['product_id' => 41, 'tag_id' => 17],
            ['product_id' => 42, 'tag_id' => 4],
            ['product_id' => 42, 'tag_id' => 5],
            ['product_id' => 42, 'tag_id' => 25],
            ['product_id' => 42, 'tag_id' => 30],
            ['product_id' => 43, 'tag_id' => 19],
            ['product_id' => 43, 'tag_id' => 30],
            ['product_id' => 44, 'tag_id' => 5],
            ['product_id' => 44, 'tag_id' => 10],
            ['product_id' => 44, 'tag_id' => 12],
            ['product_id' => 44, 'tag_id' => 27],
            ['product_id' => 45, 'tag_id' => 4],
            ['product_id' => 45, 'tag_id' => 10],
            ['product_id' => 45, 'tag_id' => 29],
            ['product_id' => 46, 'tag_id' => 7],
            ['product_id' => 47, 'tag_id' => 13],
            ['product_id' => 48, 'tag_id' => 13],
            ['product_id' => 49, 'tag_id' => 8],
            ['product_id' => 49, 'tag_id' => 9],
            ['product_id' => 49, 'tag_id' => 11],
            ['product_id' => 50, 'tag_id' => 25],
        ];
    }
}

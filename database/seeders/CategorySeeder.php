<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::data() as $cat) {
            Category::query()->forceCreate($cat);
        }
    }

    /** @return list<array{id: int, name: string, slug: string, description: string, parent_id: int|null, active: bool}> */
    public static function data(): array
    {
        return [
            ['id' => 1, 'name' => 'Ceramicas', 'slug' => 'ceramicas', 'description' => 'Pecas artesanais em ceramica, modeladas a mao com acabamentos unicos.', 'parent_id' => null, 'active' => true],
            ['id' => 2, 'name' => 'Vasos e Cachepots', 'slug' => 'vasos-e-cachepots', 'description' => 'Vasos decorativos e cachepots artesanais para plantas e composicoes.', 'parent_id' => 1, 'active' => true],
            ['id' => 3, 'name' => 'Pratos e Tigelas', 'slug' => 'pratos-e-tigelas', 'description' => 'Pratos e tigelas feitos a mao com esmaltes naturais e formas organicas.', 'parent_id' => 1, 'active' => true],
            ['id' => 4, 'name' => 'Canecas e Xicaras', 'slug' => 'canecas-e-xicaras', 'description' => 'Canecas e xicaras artesanais com acabamento unico em cada peca.', 'parent_id' => 1, 'active' => true],
            ['id' => 5, 'name' => 'Pecas Decorativas', 'slug' => 'pecas-decorativas', 'description' => 'Esculturas, objetos e pecas decorativas em ceramica artistica.', 'parent_id' => 1, 'active' => true],
            ['id' => 6, 'name' => 'Texteis', 'slug' => 'texteis', 'description' => 'Tecidos, almofadas, tapetes e acessorios feitos a mao com fibras naturais.', 'parent_id' => null, 'active' => true],
            ['id' => 7, 'name' => 'Almofadas e Mantas', 'slug' => 'almofadas-e-mantas', 'description' => 'Almofadas e mantas artesanais em tecidos naturais e tingimentos manuais.', 'parent_id' => 6, 'active' => true],
            ['id' => 8, 'name' => 'Tapetes Artesanais', 'slug' => 'tapetes-artesanais', 'description' => 'Tapetes tecidos e trancados a mao com fibras naturais e tecnicas tradicionais.', 'parent_id' => 6, 'active' => true],
            ['id' => 9, 'name' => 'Bolsas e Sacolas', 'slug' => 'bolsas-e-sacolas', 'description' => 'Bolsas e sacolas artesanais em tecidos naturais, couro e materiais sustentaveis.', 'parent_id' => 6, 'active' => true],
            ['id' => 10, 'name' => 'Tecidos e Panos de Prato', 'slug' => 'tecidos-e-panos-de-prato', 'description' => 'Tecidos estampados a mao e panos de prato com bordados artesanais.', 'parent_id' => 6, 'active' => true],
            ['id' => 11, 'name' => 'Arte e Gravuras', 'slug' => 'arte-e-gravuras', 'description' => 'Obras de arte originais, prints e gravuras para decoracao de ambientes.', 'parent_id' => null, 'active' => true],
            ['id' => 12, 'name' => 'Prints e Ilustracoes', 'slug' => 'prints-e-ilustracoes', 'description' => 'Reproducoes de alta qualidade de ilustracoes originais em papel especial.', 'parent_id' => 11, 'active' => true],
            ['id' => 13, 'name' => 'Aquarelas Originais', 'slug' => 'aquarelas-originais', 'description' => 'Pinturas originais em aquarela, assinadas e com certificado de autenticidade.', 'parent_id' => 11, 'active' => true],
            ['id' => 14, 'name' => 'Fotografias de Arte', 'slug' => 'fotografias-de-arte', 'description' => 'Fotografias artisticas impressas em papel fine art com tiragem limitada.', 'parent_id' => 11, 'active' => true],
            ['id' => 15, 'name' => 'Quadros e Molduras', 'slug' => 'quadros-e-molduras', 'description' => 'Quadros prontos e molduras artesanais em madeira para obras e prints.', 'parent_id' => 11, 'active' => true],
            ['id' => 16, 'name' => 'Papelaria', 'slug' => 'papelaria', 'description' => 'Cadernos, cartoes e acessorios de papelaria feitos a mao com materiais especiais.', 'parent_id' => null, 'active' => true],
            ['id' => 17, 'name' => 'Cadernos Artesanais', 'slug' => 'cadernos-artesanais', 'description' => 'Cadernos encadernados a mao com costuras e capas artesanais.', 'parent_id' => 16, 'active' => true],
            ['id' => 18, 'name' => 'Cartoes e Envelopes', 'slug' => 'cartoes-e-envelopes', 'description' => 'Cartoes em letterpress, tipografia e impressao artesanal com envelopes.', 'parent_id' => 16, 'active' => true],
            ['id' => 19, 'name' => 'Adesivos e Selos', 'slug' => 'adesivos-e-selos', 'description' => 'Adesivos ilustrados e selos decorativos para personalizar correspondencias.', 'parent_id' => 16, 'active' => true],
            ['id' => 20, 'name' => 'Washi Tape e Decoracao', 'slug' => 'washi-tape-e-decoracao', 'description' => 'Fitas decorativas washi tape e acessorios para decoracao de papelaria.', 'parent_id' => 16, 'active' => true],
            ['id' => 21, 'name' => 'Joias Artesanais', 'slug' => 'joias-artesanais', 'description' => 'Joias e bijuterias feitas a mao com pedras naturais, metais e resina.', 'parent_id' => null, 'active' => true],
            ['id' => 22, 'name' => 'Brincos', 'slug' => 'brincos', 'description' => 'Brincos artesanais em resina, ceramica, metais e pedras naturais.', 'parent_id' => 21, 'active' => true],
            ['id' => 23, 'name' => 'Colares e Pingentes', 'slug' => 'colares-e-pingentes', 'description' => 'Colares e pingentes feitos a mao com materiais naturais e preciosos.', 'parent_id' => 21, 'active' => true],
            ['id' => 24, 'name' => 'Pulseiras e Aneis', 'slug' => 'pulseiras-e-aneis', 'description' => 'Pulseiras e aneis artesanais em couro, prata e materiais naturais.', 'parent_id' => 21, 'active' => true],
            ['id' => 25, 'name' => 'Broches e Pins', 'slug' => 'broches-e-pins', 'description' => 'Broches e pins decorativos em ceramica, metal e resina pintados a mao.', 'parent_id' => 21, 'active' => true],
            ['id' => 26, 'name' => 'Velas e Aromaterapia', 'slug' => 'velas-e-aromaterapia', 'description' => 'Velas artesanais, incensos e difusores com aromas naturais e essencias botanicas.', 'parent_id' => null, 'active' => true],
            ['id' => 27, 'name' => 'Velas de Soja', 'slug' => 'velas-de-soja', 'description' => 'Velas artesanais de cera de soja com oleos essenciais e pavios de algodao.', 'parent_id' => 26, 'active' => true],
            ['id' => 28, 'name' => 'Incensos e Porta-Incensos', 'slug' => 'incensos-e-porta-incensos', 'description' => 'Incensos naturais e porta-incensos artesanais em ceramica e madeira.', 'parent_id' => 26, 'active' => true],
            ['id' => 29, 'name' => 'Difusores e Oleos Essenciais', 'slug' => 'difusores-e-oleos-essenciais', 'description' => 'Difusores de ambiente com varetas e oleos essenciais puros.', 'parent_id' => 26, 'active' => true],
            ['id' => 30, 'name' => 'Decoracao', 'slug' => 'decoracao', 'description' => 'Objetos decorativos artesanais para transformar ambientes com personalidade.', 'parent_id' => null, 'active' => true],
            ['id' => 31, 'name' => 'Objetos Esculturais', 'slug' => 'objetos-esculturais', 'description' => 'Esculturas e objetos de arte decorativos em diversos materiais.', 'parent_id' => 30, 'active' => true],
            ['id' => 32, 'name' => 'Porta-Retratos', 'slug' => 'porta-retratos', 'description' => 'Porta-retratos artesanais em madeira, ceramica e materiais reciclados.', 'parent_id' => 30, 'active' => true],
            ['id' => 33, 'name' => 'Organizadores', 'slug' => 'organizadores', 'description' => 'Organizadores artesanais em madeira e ceramica para mesa e ambientes.', 'parent_id' => 30, 'active' => true],
            ['id' => 34, 'name' => 'Moveis Artesanais Pequenos', 'slug' => 'moveis-artesanais-pequenos', 'description' => 'Pecas de mobiliario artesanal em escala reduzida para decoracao.', 'parent_id' => 30, 'active' => true],
            ['id' => 35, 'name' => 'Jardim e Natureza', 'slug' => 'jardim-e-natureza', 'description' => 'Vasos, ferramentas e pecas para jardim com toque artesanal e natural.', 'parent_id' => null, 'active' => true],
            ['id' => 36, 'name' => 'Vasos para Plantas', 'slug' => 'vasos-para-plantas', 'description' => 'Vasos artesanais em ceramica, cimento e fibra para plantas e jardins.', 'parent_id' => 35, 'active' => true],
            ['id' => 37, 'name' => 'Ferramentas Artesanais', 'slug' => 'ferramentas-artesanais', 'description' => 'Ferramentas de jardim com cabos em madeira e acabamento artesanal.', 'parent_id' => 35, 'active' => true],
            ['id' => 38, 'name' => 'Kokedamas e Terrarios', 'slug' => 'kokedamas-e-terrarios', 'description' => 'Kokedamas, terrarios e composicoes botanicas com tecnicas artesanais.', 'parent_id' => 35, 'active' => true],
        ];
    }
}

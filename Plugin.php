<?php

namespace DataAnonymization;

require_once __DIR__ . '/vendor/autoload.php';

use Faker\Factory;
use InvalidArgumentException;
use MapasCulturais\App;
use stdClass;


class Plugin extends \MapasCulturais\Plugin
{
    protected static $instance = null;

    function __construct($config = [])
    {
        $config += [];

        parent::__construct($config);

        self::$instance = $this;
    }

    public function _init()
    {
        $app = App::i();

        $app->hook('entity(Registration).setAgentsData(1)', function ($agentsData) {
            $this->agentsData = $agentsData;
        });
    }

    public function register() {}

    /**
     * @return Plugin 
     */
    public static function getInstance(): Plugin
    {
        return self::$instance;
    }

    /**
     * @param mixed $name 
     * @return string 
     */
    public function getSocialName($name): string
    {
        $nameParts = explode(' ', $name);
        $pos = (count($nameParts) - 1);
        return $nameParts[0] . ' ' . $nameParts[$pos];
    }


    /**
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function buildNewData($agent): array
    {
        $faker = Factory::create('pt_BR');

        $agent_type = $agent->id;
        $full_name = $agent_type == 1 ? $faker->name : $faker->company;
        $document = $agent_type == 1 ? $faker->cpf : $faker->cnpj;

        $dados = [
            'name' => $full_name,
            'nomeCompleto' => $full_name,
            'nomeSocial' => $this->getSocialName($full_name),
            'cnpj' => $agent_type == 2 ? $document : "",
            'cpf' => $agent_type == 1 ? $document : "",
            'emailPublico' => $faker->email,
            'emailPrivado' => $faker->email,
            'telefonePublico' => $faker->cellphoneNumber,
            'telefone1' => $faker->cellphoneNumber,
            'telefone2' => $faker->phoneNumber,
            'En_CEP' => $faker->postcode,
            'En_Nome_Logradouro' => $faker->streetName,
            'En_Num' => $faker->buildingNumber,
            'En_Bairro' => $faker->streetSuffix,
            'En_Estado' => $faker->stateAbbr,
            'En_Municipio' => $faker->city,
            'site' => "",
            'facebook' => "",
            'twitter' => "",
            'instagram' => "",
            'linkedin' => "",
            'vimeo' => "",
            'spotify' => "",
            'youtube' => "",
            'pinterest' => "",
            'tiktok' => "",
            'facebook' => "",
            'facebook' => "",
        ];

        return $dados;
    }
    
}

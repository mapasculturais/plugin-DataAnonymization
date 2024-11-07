<?php

use MapasCulturais\App;
use DataAnonymization\Plugin;
use MapasCulturais\Entities\Registration;

$app = MapasCulturais\App::i();
$em = $app->em;
$conn = $em->getConnection();
$plugin = Plugin::getInstance();

function __table_exists($table_name)
{
    $app = App::i();
    $em = $app->em;
    /** @var Connection $conn */
    $conn = $em->getConnection();

    if ($conn->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table_name';")) {
        return true;
    } else {
    }
}


return [
    'Realiza a anonimização dos dados na base de agentes' => function () use ($app, $conn, $plugin) {
        if ($agents = $conn->fetchAll("SELECT * FROM agent")) {
            foreach ($agents as $agent) {
                $old_data = (object) $agent;
                $new_data = (object) $plugin->buildNewData($old_data);

                $conn->executeQuery("UPDATE agent set name = :name WHERE id = :id", [
                    'name' => $new_data->name,
                    'id' => $old_data->id
                ]);

                foreach ($new_data as $field => $value) {
                    $conn->executeQuery("UPDATE agent_meta set value = :val WHERE object_id = :obj_id and key = :field", [
                        'val' => $value,
                        'obj_id' => $old_data->id,
                        'field' => $field
                    ]);
                }

                $conn->executeQuery("UPDATE usr set email = :mail WHERE id = :id", [
                    'mail' => $new_data->emailPrivado,
                    'id' => $old_data->id
                ]);

                $app->log->debug("Realiza a anonimização dos dados do agente {$old_data->id}");
            }
        }
    },

    'Realiza a anonimização dos dados na base de inscrições' => function () use ($app, $conn, $plugin) {
        if ($registrations = $conn->fetchAll("SELECT * FROM registration")) {

            foreach ($registrations as $registration) {
                $registration_id = $registration['id'];
                $agents_data = json_decode($registration['agents_data']);

                if (isset($agents_data->owner)) {
                    $owner = $conn->fetchAll("SELECT * FROM agent WHERE id = {$agents_data->owner->id}");
                    $agent_meta = $conn->fetchAll("SELECT * FROM agent_meta WHERE object_id = {$agents_data->owner->id}");

                    $agents_data->owner->name = $owner[0]['name'];
                    foreach ($agent_meta as $meta) {
                        $field = $meta['key'];
                        $value = $meta['value'];
                        $agents_data->owner->$field = $value;
                    }

                    $app->log->debug("Realiza a anonimização dos dados do agente responsável {$agents_data->owner->id} na inscrição {$registration_id}");
                }

                if (isset($agents_data->coletivo)) {
                    $coletivo = $conn->fetchAll("SELECT * FROM agent WHERE id = {$agents_data->coletivo->id}");
                    $agent_meta = $conn->fetchAll("SELECT * FROM agent_meta WHERE object_id = {$agents_data->coletivo->id}");

                    $agents_data->coletivo->name = $coletivo[0]['name'];
                    foreach ($agent_meta as $meta) {
                        $field = $meta['key'];
                        $value = $meta['value'];
                        $agents_data->coletivo->$field = $value;
                    }

                    $app->log->debug("Realiza a anonimização dos dados do agente coletivo {$agents_data->coletivo->id} na inscrição {$registration_id}");
                }

                if (isset($agents_data->instituicao)) {
                    $instituicao = $conn->fetchAll("SELECT * FROM agent WHERE id = {$agents_data->instituicao->id}");
                    $agent_meta = $conn->fetchAll("SELECT * FROM agent_meta WHERE object_id = {$agents_data->instituicao->id}");

                    $agents_data->instituicao->name = $instituicao[0]['name'];
                    foreach ($agent_meta as $meta) {
                        $field = $meta['key'];
                        $value = $meta['value'];
                        $agents_data->instituicao->$field = $value;
                    }

                    $app->log->debug("Realiza a anonimização dos dados da instituicao responsável {$agents_data->instituicao->id} na inscrição {$registration_id}");
                }


                $_agents_data = json_encode($agents_data);

                $conn->executeQuery("UPDATE registration set agents_data = :agent_data WHERE id = :id", [
                    'agent_data' => $_agents_data,
                    'id' => $registration['id']
                ]);
            }
        }

    },


    'Apaga os dados das tabelas relacionadas ao blame' => function () use ($app) {
        $em = $app->em;
        $conn = $em->getConnection();

        if (__table_exists('blame_request')) {
            $conn->executeQuery("DELETE FROM blame_request");
        }

        if (__table_exists('blame_log')) {
            $conn->executeQuery("DELETE FROM blame_log");
        }
    },

    'Apaga os dados das tabelas relacionadas às revisões' => function () use ($app) {
        $em = $app->em;
        $conn = $em->getConnection();

        $batch_size = 1000;
        $offset = 0;
        $count = 0;
        do {
            $revisions = $conn->fetchAll("
                SELECT id 
                FROM entity_revision 
                WHERE object_type = 'MapasCulturais\\Entities\\Registration' 
                OR object_type = 'MapasCulturais\\Entities\\Agent' 
                LIMIT {$batch_size} OFFSET {$offset}
            ");

            if (!$revisions) {
                break;
            }

            $revisions_ids = array_column($revisions, 'id');
            $_revisions_ids = implode(",", $revisions_ids);

            $relations = $conn->fetchAll("
                SELECT revision_data_id 
                FROM entity_revision_revision_data 
                WHERE revision_id IN ({$_revisions_ids})
            ");

            $revision_data_ids = array_column($relations, 'revision_data_id');
            $_revision_data_ids = implode(",", $revision_data_ids);

            if ($revision_data_ids) {
                $conn->executeQuery("DELETE FROM entity_revision_data WHERE id IN ({$_revision_data_ids})");
            }
            $conn->executeQuery("DELETE FROM entity_revision_revision_data WHERE revision_id IN ({$_revisions_ids})");
            $conn->executeQuery("DELETE FROM entity_revision WHERE id IN ({$_revisions_ids})");

            $offset += $batch_size;

            $app->log->debug("{$count} - Realiza a anonimização dos dados das tabelas de revisões");
            $count ++;
        } while (count($revisions) === $batch_size);
    },
    'Recria as revisões de inscrições após anonimização dos dados' => function () use ($app) {
        $em = $app->em;
        $conn = $em->getConnection();

        if ($registrations = $conn->fetchAll("SELECT id FROM registration")) {

            /** @var Registration $registration */
            foreach ($registrations as $key => $registration) {
                if ($reg = $app->repo('Registration')->find($registration['id'])) {
                    $reg->_newCreatedRevision();
                    $reg->_newModifiedRevision();
                }
                
                $app->log->debug("{$key} - Recria as revisões da inscrição {$reg->id} após anonimização");
                $app->em->clear();
            }
        }
    },
    'Recria as revisões de agentes após anonimização dos dados' => function () use ($app) {
        $em = $app->em;
        $conn = $em->getConnection();

        if ($agents = $conn->fetchAll("SELECT id FROM agent")) {

            /** @var Registration $registration */
            foreach ($agents as $key => $agent) {
                if ($agent = $app->repo('Registration')->find($agent['id'])) {
                    $agent->_newCreatedRevision();
                    $agent->_newModifiedRevision();
                }

                $app->log->debug("{$key} - Recria as revisões da inscrição {$agent->id} após anonimização");
                $app->em->clear();
            }
        }
    }

];

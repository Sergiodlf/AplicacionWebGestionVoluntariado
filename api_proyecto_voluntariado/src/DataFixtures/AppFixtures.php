<?php

namespace App\DataFixtures;

use App\Entity\Ciclo;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Entity\Administrador;
use App\Entity\ODS;
use App\Entity\Habilidad;
use App\Entity\Interes;
use App\Entity\Necesidad;
use App\Entity\Actividad;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Kreait\Firebase\Contract\Auth;

class AppFixtures extends Fixture
{
    public function __construct(
        private Auth $firebaseAuth
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. DATA DEFINITIONS
        $nombres = ['Carlos', 'Ana', 'Lucia', 'Marcos', 'Elena', 'Javier', 'Sara', 'David', 'Laura', 'Pablo'];
        $apellidos = ['Lopez', 'Martinez', 'Ruiz', 'Gomez', 'Fernandez', 'Garcia', 'Perez', 'Sanchez', 'Diaz', 'Rodríguez'];
        $sectores = ['Medio Ambiente', 'Social', 'Animales', 'Vivienda', 'Educación', 'Salud', 'Comunitario'];
        $habilidadesData = ['Programación', 'Diseño Gráfico', 'Redes Sociales', 'Gestión de Eventos', 'Docencia', 'Primeros Auxilios', 'Cocina', 'Idiomas'];
        $interesesData = ['Medio Ambiente', 'Educación', 'Salud', 'Animales', 'Cultura', 'Tecnología', 'Derechos Humanos'];
        $ciudades = ['Pamplona', 'Madrid', 'Barcelona', 'Sevilla', 'Valencia'];

        // 2. SEED BASIC ENTITIES (Habilidades, Intereses, ODS, Ciclos)
        echo "🌱 Seeding basic entities...\n";
        
        $habilidades = [];
        foreach ($habilidadesData as $name) {
            $h = new Habilidad();
            $h->setNombre($name);
            $manager->persist($h);
            $habilidades[] = $h;
        }

        $intereses = [];
        foreach ($interesesData as $name) {
            $i = new Interes();
            $i->setNombre($name);
            $manager->persist($i);
            $intereses[] = $i;
        }

        $odsData = [
            ['nombre' => 'Fin de la pobreza', 'color' => '#E5243B'],
            ['nombre' => 'Hambre cero', 'color' => '#DDA63A'],
            ['nombre' => 'Salud y bienestar', 'color' => '#4C9F38'],
            ['nombre' => 'Educación de calidad', 'color' => '#C5192D'],
        ];
        $odsEntities = [];
        foreach ($odsData as $data) {
            $o = new ODS();
            $o->setNombre($data['nombre']);
            $o->setColor($data['color']);
            $manager->persist($o);
            $odsEntities[] = $o;
        }

        $ciclos = [];
        $ciclosData = ['DAM', 'DAW', 'ASIR', 'Marketing', 'Administración'];
        foreach ($ciclosData as $name) {
            $c = new Ciclo();
            $c->setNombre($name);
            $c->setCurso(2);
            $manager->persist($c);
            $ciclos[] = $c;
        }

        $manager->flush();

        // 3. GENERATE MASS VOLUNTEERS (10)
        echo "👤 Generating 10 Volunteers...\n";
        for ($i = 0; $i < 10; $i++) {
            $nombre = $nombres[$i % count($nombres)];
            $apellido1 = $apellidos[$i % count($apellidos)];
            $email = strtolower($nombre . '.' . $apellido1 . $i . '@test.com');
            $dni = str_pad((string)$i, 8, '0', STR_PAD_LEFT) . 'V';

            $this->upsertFirebaseUser($email, '123456', "$nombre $apellido1", ['rol' => 'voluntario']);

            $vol = new Voluntario();
            $vol->setDni($dni);
            $vol->setNombre($nombre);
            $vol->setApellido1($apellido1);
            $vol->setApellido2($apellidos[($i + 1) % count($apellidos)]);
            $vol->setCorreo($email);
            $vol->setZona($ciudades[array_rand($ciudades)]);
            $vol->setFechaNacimiento(new \DateTime('2000-01-01'));
            $vol->setExperiencia('Usuario de prueba generado via Fixtures.');
            $vol->setCoche(true);
            $vol->setEstadoVoluntario('ACEPTADO');
            $vol->setCiclo($ciclos[array_rand($ciclos)]);
            $vol->addHabilidad($habilidades[array_rand($habilidades)]);
            $vol->addInterese($intereses[array_rand($intereses)]);

            $manager->persist($vol);
        }

        // 4. GENERATE MASS ORGANIZATIONS (5)
        echo "🏢 Generating 5 Organizations...\n";
        $orgNames = ['EcoVida', 'AyudaDirecta', 'Huellas Felices', 'Techo Para Todos', 'Mentes Brillantes'];
        $orgs = [];
        for ($i = 0; $i < 5; $i++) {
            $name = $orgNames[$i % count($orgNames)];
            $email = strtolower(str_replace(' ', '', $name) . $i . '@test.com');
            $cif = 'B' . str_pad((string)$i, 8, '0', STR_PAD_LEFT);

            $this->upsertFirebaseUser($email, '123456', $name, ['rol' => 'organizacion']);

            $org = new Organizacion();
            $org->setCif($cif);
            $org->setNombre($name);
            $org->setEmail($email);
            $org->setDireccion("Calle Solidaria $i");
            $org->setLocalidad("Pamplona");
            $org->setCp("31000");
            $org->setSector($sectores[$i % count($sectores)]);
            $org->setEstado('aprobado');
            $org->setDescripcion("Labor social enfocada en " . $org->getSector());
            $org->setContacto("600" . str_pad((string)$i, 6, '0', STR_PAD_LEFT));

            $manager->persist($org);
            $orgs[] = $org;
        }

        // 5. CREATE ADMIN MASTER
        echo "⛑️ Creating Admin...\n";
        $adminEmail = 'admin@curso.com';
        $this->upsertFirebaseUser($adminEmail, 'admin123', 'Admin Master', ['rol' => 'admin', 'admin' => true]);

        $admin = new Administrador();
        $admin->setEmail($adminEmail);
        $admin->setNombre('Admin Master');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // 6. GENERATE SOME ACTIVITIES
        echo "📅 Generating Activities...\n";
        for ($i = 0; $i < 3; $i++) {
            $act = new Actividad();
            $act->setNombre("Actividad Social $i");
            $act->setDireccion("Centro Comunitario $i");
            $act->setFechaInicio(new \DateTime('+1 month'));
            $act->setFechaFin(new \DateTime('+1 month + 4 hours'));
            $act->setMaxParticipantes(10);
            $act->setEstado('ABIERTA');
            $act->setEstadoAprobacion('ACEPTADO');
            $act->setOrganizacion($orgs[$i % count($orgs)]);
            $act->addOd($odsEntities[array_rand($odsEntities)]);
            $act->setSector($sectores[array_rand($sectores)]);
            $act->setDescripcion("Únete a nuestra causa para mejorar el entorno.");
            $manager->persist($act);
        }

        $manager->flush();
        echo "✅ Fixtures loaded successfully with Firebase sync!\n";
    }

    private function upsertFirebaseUser(string $email, string $password, string $displayName, array $claims): void
    {
        try {
            try {
                $user = $this->firebaseAuth->getUserByEmail($email);
                $uid = $user->uid;
                $this->firebaseAuth->updateUser($uid, [
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => $displayName
                ]);
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                $user = $this->firebaseAuth->createUser([
                    'email' => $email,
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => $displayName
                ]);
                $uid = $user->uid;
            }
            $this->firebaseAuth->setCustomUserClaims($uid, $claims);
        } catch (\Throwable $e) {
            echo "   ⚠️ Firebase Warning ($email): " . $e->getMessage() . "\n";
        }
    }
}

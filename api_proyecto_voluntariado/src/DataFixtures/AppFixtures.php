<?php

namespace App\DataFixtures;

use App\Enum\VolunteerStatus;
use App\Enum\OrganizationStatus;
use App\Enum\ActivityStatus;
use App\Enum\ActivityApproval;
use App\Entity\Ciclo;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Entity\Administrador;
use App\Entity\ODS;
use App\Entity\Habilidad;
use App\Entity\Interes;
use App\Entity\Necesidad;
use App\Entity\Actividad;
use App\Service\FirebaseServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private FirebaseServiceInterface $firebaseService
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
        $idiomasList = ['Inglés', 'Francés', 'Alemán', 'Euskera', 'Italiano'];
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $turnos = ['Mañana', 'Tarde', 'Noche'];

        // 2. SEED BASIC ENTITIES (Habilidades, Intereses, ODS, Ciclos) - IDEMPOTENT
        echo "🌱 Seeding basic entities...\n";
        
        $habilidades = [];
        foreach ($habilidadesData as $name) {
            $h = $manager->getRepository(Habilidad::class)->findOneBy(['nombre' => $name]);
            if (!$h) {
                $h = new Habilidad();
                $h->setNombre($name);
                $manager->persist($h);
                echo "  ✨ Created Habilidad: $name\n";
            } else {
                echo "  ♻️ Habilidad already exists: $name\n";
            }
            $habilidades[] = $h;
        }

        $intereses = [];
        foreach ($interesesData as $name) {
            $i = $manager->getRepository(Interes::class)->findOneBy(['nombre' => $name]);
            if (!$i) {
                $i = new Interes();
                $i->setNombre($name);
                $manager->persist($i);
                echo "  ✨ Created Interes: $name\n";
            } else {
                echo "  ♻️ Interes already exists: $name\n";
            }
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
            $o = $manager->getRepository(ODS::class)->findOneBy(['nombre' => $data['nombre']]);
            if (!$o) {
                $o = new ODS();
                $o->setNombre($data['nombre']);
                $o->setColor($data['color']);
                $manager->persist($o);
                echo "  ✨ Created ODS: {$data['nombre']}\n";
            } else {
                echo "  ♻️ ODS already exists: {$data['nombre']}\n";
            }
            $odsEntities[] = $o;
        }

        $ciclos = [];
        $ciclosData = ['DAM', 'DAW', 'ASIR', 'Marketing', 'Administración'];
        foreach ($ciclosData as $name) {
            $c = $manager->getRepository(Ciclo::class)->findOneBy(['nombre' => $name]);
            if (!$c) {
                $c = new Ciclo();
                $c->setNombre($name);
                $c->setCurso(2);
                $manager->persist($c);
                echo "  ✨ Created Ciclo: $name\n";
            } else {
                echo "  ♻️ Ciclo already exists: $name\n";
            }
            $ciclos[] = $c;
        }

        $manager->flush();

        // 3. CREATE SPECIFIC TEST USERS (as documented in README) - IDEMPOTENT
        echo "🎯 Creating specific test users (for README)...\n";
        
        // 3A. TEST VOLUNTEER: voluntario_test@curso.com
        $testVolEmail = 'voluntario_test@curso.com';
        $testVol = $manager->getRepository(Voluntario::class)->findOneBy(['correo' => $testVolEmail]);
        if (!$testVol) {
            $this->upsertFirebaseUser($testVolEmail, '123456', 'Voluntario Test', ['rol' => 'voluntario']);
            
            $testVol = new Voluntario();
            $testVol->setDni('12345678T');
            $testVol->setNombre('Voluntario');
            $testVol->setApellido1('Test');
            $testVol->setApellido2('Usuario');
            $testVol->setCorreo($testVolEmail);
            $testVol->setZona('Pamplona');
            $testVol->setFechaNacimiento(new \DateTime('1995-01-01'));
            $testVol->setExperiencia('Usuario de prueba documentado en el README.');
            $testVol->setCoche(true);
            $testVol->setEstadoVoluntario(VolunteerStatus::ACEPTADO);
            $testVol->setCiclo($ciclos[0]);
            $testVol->addHabilidad($habilidades[0]);
            $testVol->addInterese($intereses[0]);
            $testVol->setIdiomas(['Inglés', 'Euskera']);
            $testVol->setDisponibilidad(['Lunes Mañana', 'Miércoles Tarde']);
            
            $manager->persist($testVol);
            echo "  ✨ Created TEST Voluntario: $testVolEmail\n";
        } else {
            // Sync with Firebase even if exists
            $this->upsertFirebaseUser($testVolEmail, '123456', 'Voluntario Test', ['rol' => 'voluntario']);
            echo "  ♻️ TEST Voluntario already exists: $testVolEmail\n";
        }
        
        // 3B. TEST ORGANIZATION: organizacion_test@curso.com
        $testOrgEmail = 'organizacion_test@curso.com';
        $testOrg = $manager->getRepository(Organizacion::class)->findOneBy(['email' => $testOrgEmail]);
        if (!$testOrg) {
            $this->upsertFirebaseUser($testOrgEmail, '123456', 'Organización Test', ['rol' => 'organizacion']);
            
            $testOrg = new Organizacion();
            $testOrg->setCif('B99999999');
            $testOrg->setNombre('Organización Test');
            $testOrg->setEmail($testOrgEmail);
            $testOrg->setDireccion('Calle Test 123');
            $testOrg->setLocalidad('Pamplona');
            $testOrg->setCp('31000');
            $testOrg->setSector('Social');
            $testOrg->setEstado(OrganizationStatus::APROBADO);
            $testOrg->setDescripcion('Organización de prueba documentada en el README.');
            $testOrg->setContacto('600000000');
            
            $manager->persist($testOrg);
            echo "  ✨ Created TEST Organización: $testOrgEmail\n";
        } else {
            // Sync with Firebase even if exists
            $this->upsertFirebaseUser($testOrgEmail, '123456', 'Organización Test', ['rol' => 'organizacion']);
            echo "  ♻️ TEST Organización already exists: $testOrgEmail\n";
        }
        
        $manager->flush();

        // 4. GENERATE MASS VOLUNTEERS (10) - IDEMPOTENT
        echo "👤 Generating 10 Volunteers...\n";
        for ($i = 0; $i < 10; $i++) {
            $nombre = $nombres[$i % count($nombres)];
            $apellido1 = $apellidos[$i % count($apellidos)];
            $email = strtolower($nombre . '.' . $apellido1 . $i . '@test.com');
            $dni = str_pad((string)$i, 8, '0', STR_PAD_LEFT) . 'V';

            // Check if volunteer already exists
            $vol = $manager->getRepository(Voluntario::class)->findOneBy(['correo' => $email]);
            if ($vol) {
                echo "  ♻️ Voluntario already exists: $email\n";
                continue;
            }

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
            $vol->setEstadoVoluntario(VolunteerStatus::ACEPTADO);
            $vol->setCiclo($ciclos[array_rand($ciclos)]);
            $vol->addHabilidad($habilidades[array_rand($habilidades)]);
            $vol->addInterese($intereses[array_rand($intereses)]);
            
            // Random languages (1 or 2)
            $numIdiomas = rand(1, 2);
            $volIdiomas = [];
            for($j=0; $j<$numIdiomas; $j++) {
                $volIdiomas[] = $idiomasList[array_rand($idiomasList)];
            }
            $vol->setIdiomas(array_unique($volIdiomas));

            // Random availability
            $dia = $diasSemana[array_rand($diasSemana)];
            $turno = $turnos[array_rand($turnos)];
            $vol->setDisponibilidad(["$dia $turno"]);

            $manager->persist($vol);
            echo "  ✨ Created Voluntario: $email\n";
        }

        // 5. GENERATE MASS ORGANIZATIONS (5) - IDEMPOTENT
        echo "🏢 Generating 5 Organizations...\n";
        $orgNames = ['EcoVida', 'AyudaDirecta', 'Huellas Felices', 'Techo Para Todos', 'Mentes Brillantes'];
        $orgs = [];
        for ($i = 0; $i < 5; $i++) {
            $name = $orgNames[$i % count($orgNames)];
            $email = strtolower(str_replace(' ', '', $name) . $i . '@test.com');
            $cif = 'B' . str_pad((string)$i, 8, '0', STR_PAD_LEFT);

            // Check if organization already exists
            $org = $manager->getRepository(Organizacion::class)->findOneBy(['email' => $email]);
            if ($org) {
                echo "  ♻️ Organización already exists: $email\n";
                $orgs[] = $org;
                continue;
            }

            $this->upsertFirebaseUser($email, '123456', $name, ['rol' => 'organizacion']);

            $org = new Organizacion();
            $org->setCif($cif);
            $org->setNombre($name);
            $org->setEmail($email);
            $org->setDireccion("Calle Solidaria $i");
            $org->setLocalidad("Pamplona");
            $org->setCp("31000");
            $org->setSector($sectores[$i % count($sectores)]);
            $org->setEstado(OrganizationStatus::APROBADO);
            $org->setDescripcion("Labor social enfocada en " . $org->getSector());
            $org->setContacto("600" . str_pad((string)$i, 6, '0', STR_PAD_LEFT));

            $manager->persist($org);
            $orgs[] = $org;
            echo "  ✨ Created Organización: $email\n";
        }

        // 6. CREATE ADMIN MASTER - IDEMPOTENT
        echo "⛑️ Creating Admin...\n";
        $adminEmail = 'admin@curso.com';
        $admin = $manager->getRepository(Administrador::class)->findOneBy(['email' => $adminEmail]);
        
        if (!$admin) {
            $this->upsertFirebaseUser($adminEmail, 'admin123', 'Admin Master', ['rol' => 'admin', 'admin' => true]);

            $admin = new Administrador();
            $admin->setEmail($adminEmail);
            $admin->setNombre('Admin Master');
            $admin->setRoles(['ROLE_ADMIN']);
            $manager->persist($admin);
            echo "  ✨ Created Admin: $adminEmail\n";
        } else {
            // Still sync with Firebase in case Firebase user was deleted
            $this->upsertFirebaseUser($adminEmail, 'admin123', 'Admin Master', ['rol' => 'admin', 'admin' => true]);
            echo "  ♻️ Admin already exists: $adminEmail\n";
        }

        // 7. GENERATE SOME ACTIVITIES - IDEMPOTENT
        echo "📅 Generating Activities...\n";
        for ($i = 0; $i < 3; $i++) {
            $activityName = "Actividad Social $i";
            
            // Check if activity already exists (by name and organization)
            $existingAct = $manager->getRepository(Actividad::class)->findOneBy([
                'nombre' => $activityName,
                'organizacion' => $orgs[$i % count($orgs)]
            ]);
            
            if ($existingAct) {
                echo "  ♻️ Actividad already exists: $activityName\n";
                continue;
            }

            $act = new Actividad();
            $act->setNombre($activityName);
            $act->setDireccion("Centro Comunitario $i");
            $act->setFechaInicio(new \DateTime('+1 month'));
            $act->setFechaFin(new \DateTime('+1 month + 4 hours'));
            $act->setMaxParticipantes(10);
            $act->setEstado(ActivityStatus::EN_CURSO);
            $act->setEstadoAprobacion(ActivityApproval::ACEPTADA);
            $act->setOrganizacion($orgs[$i % count($orgs)]);
            $act->addOd($odsEntities[array_rand($odsEntities)]);
            $act->setSector($sectores[array_rand($sectores)]);
            $act->setDescripcion("Únete a nuestra causa para mejorar el entorno.");
            $manager->persist($act);
            echo "  ✨ Created Actividad: $activityName\n";
        }

        $manager->flush();
        echo "✅ Fixtures loaded successfully with Firebase sync!\n";
    }

    private function upsertFirebaseUser(string $email, string $password, string $displayName, array $claims): void
    {
        try {
            $this->firebaseService->syncUser($email, $password, $displayName, $claims);
        } catch (\Throwable $e) {
            echo "   ⚠️ Firebase Warning ($email): " . $e->getMessage() . "\n";
        }
    }
}

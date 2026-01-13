<?php

namespace App\DataFixtures;

use App\Entity\Ciclo;
use App\Entity\Organizacion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $conn = $manager->getConnection();

        // --- DEFINICIÓN DE DATOS DEL FRONTEND ---
        $sectores = ['Educación', 'Salud', 'Medio Ambiente', 'Comunitario'];
        $habilidades = ['Programación', 'Diseño Gráfico', 'Redes Sociales', 'Gestión de Eventos', 'Docencia', 'Primeros Auxilios', 'Cocina', 'Conducción', 'Idiomas', 'Música'];
        $intereses = ['Medio Ambiente', 'Educación', 'Salud', 'Animales', 'Cultura', 'Deporte', 'Tecnología', 'Derechos Humanos', 'Mayores', 'Infancia'];
        $disponibilidad = ['Lunes Mañana', 'Lunes Tarde', 'Martes Mañana', 'Martes Tarde', 'Miércoles Mañana', 'Miércoles Tarde', 'Jueves Mañana', 'Jueves Tarde', 'Viernes Mañana', 'Viernes Tarde', 'Fines de Semana'];
        
        $nombresVol = ['Carlos', 'Ana', 'Lucia', 'Marcos', 'Elena', 'Javier', 'Sara', 'David', 'Laura', 'Pablo'];
        $apellidos = ['Lopez', 'Martinez', 'Ruiz', 'Gomez', 'Fernandez', 'Garcia', 'Perez', 'Sanchez', 'Diaz', 'Rodríguez'];

        $ciclosData = [
            ['nombre' => 'Administración y Finanzas', 'curso' => 2],
            ['nombre' => 'Asistencia a la Dirección', 'curso' => 2],
            ['nombre' => 'Marketing y Publicidad', 'curso' => 2],
            ['nombre' => 'Transporte y Logística', 'curso' => 2],
            ['nombre' => 'Desarrollo de Aplicaciones Multiplataforma (DAM)', 'curso' => 2],
            ['nombre' => 'Desarrollo de Aplicaciones Web (DAW)', 'curso' => 2],
            ['nombre' => 'Administración de Sistemas Informáticos en Red (ASIR)', 'curso' => 2],
            ['nombre' => 'Gestión de Ventas y Espacios Comerciales', 'curso' => 2],
            ['nombre' => 'Comercio Internacional', 'curso' => 2],
        ];

        // --- 1. SEED CATEGORIES (ODS, Habilidades, Intereses, Necesidades) ---
        echo "Cargando Categorías (ODS, Habilidades, etc.)...\n";

        $odsEntities = [];
        $odsData = [
            ['nombre' => 'Fin de la pobreza', 'color' => '#E5243B'],
            ['nombre' => 'Hambre cero', 'color' => '#DDA63A'],
            ['nombre' => 'Salud y bienestar', 'color' => '#4C9F38'],
            ['nombre' => 'Educación de calidad', 'color' => '#C5192D'],
            ['nombre' => 'Igualdad de género', 'color' => '#FF3A21'],
            ['nombre' => 'Agua limpia y saneamiento', 'color' => '#26BDE2'],
            ['nombre' => 'Energía asequible y no contaminante', 'color' => '#FCC30B'],
            ['nombre' => 'Trabajo decente y crecimiento económico', 'color' => '#A21942'],
            ['nombre' => 'Industria, innovación e infraestructura', 'color' => '#FD6925'],
            ['nombre' => 'Reducción de las desigualdades', 'color' => '#DD1367'],
            ['nombre' => 'Ciudades y comunidades sostenibles', 'color' => '#FD9D24'],
            ['nombre' => 'Producción y consumo responsables', 'color' => '#BF8B2E'],
            ['nombre' => 'Acción por el clima', 'color' => '#3F7E44'],
            ['nombre' => 'Vida submarina', 'color' => '#0A97D9'],
            ['nombre' => 'Vida de ecosistemas terrestres', 'color' => '#56C02B'],
            ['nombre' => 'Paz, justicia e instituciones sólidas', 'color' => '#00689D'],
            ['nombre' => 'Alianzas para lograr los objetivos', 'color' => '#19486A'],
        ];

        foreach ($odsData as $data) {
            $ods = new \App\Entity\ODS();
            $ods->setNombre($data['nombre']);
            $ods->setColor($data['color']);
            $manager->persist($ods);
            $odsEntities[] = $ods;
        }

        $habilidadesEntities = [];
        foreach ($habilidades as $hNombre) {
            $h = new \App\Entity\Habilidad();
            $h->setNombre($hNombre);
            $manager->persist($h);
            $habilidadesEntities[] = $h;
        }

        $interesesEntities = [];
        foreach ($intereses as $iNombre) {
            $i = new \App\Entity\Interes();
            $i->setNombre($iNombre);
            $manager->persist($i);
            $interesesEntities[] = $i;
        }

        $necesidadesEntities = [];
        $necesidadesData = ['Cocina', 'Manipulación de Alimentos', 'Primeros Auxilios', 'Medio Ambiente', 'Gestión de Equipos'];
        foreach ($necesidadesData as $nNombre) {
            $n = new \App\Entity\Necesidad();
            $n->setNombre($nNombre);
            $manager->persist($n);
            $necesidadesEntities[] = $n;
        }

        $manager->flush();

        // --- 2. CICLOS ---
        echo "Cargando Ciclos...\n";
        $ciclosEntities = [];
        foreach ($ciclosData as $cData) {
            $ciclo = new Ciclo();
            $ciclo->setCurso($cData['curso']);
            $ciclo->setNombre($cData['nombre']);
            $manager->persist($ciclo);
            $ciclosEntities[] = $ciclo;
        }
        $manager->flush();

        // --- 3. ORGANIZACIONES ---
        echo "Cargando Organizaciones...\n";
        $org1 = new Organizacion();
        $org1->setCif('A12345678');
        $org1->setNombre('Cruz Roja');
        $org1->setDireccion('Calle Principal 123');
        $org1->setCp('28001');
        $org1->setLocalidad('Pamplona');
        $org1->setDescripcion('Ayuda humanitaria y servicios sociales.');
        $org1->setContacto('Juan Perez');
        $org1->setEmail('contacto@cruzroja.es');
        $org1->setPassword($this->passwordHasher->hashPassword($org1, 'password123'));
        $org1->setEstado('Aprobado');
        $org1->setSector($sectores[1]);
        $manager->persist($org1);

        $org2 = new Organizacion();
        $org2->setCif('B87654321');
        $org2->setNombre('Greenpeace');
        $org2->setDireccion('Avenida Verde 45');
        $org2->setCp('08001');
        $org2->setLocalidad('Pamplona');
        $org2->setDescripcion('Defensa del medio ambiente y la paz.');
        $org2->setContacto('Maria Garcia');
        $org2->setEmail('info@greenpeace.es');
        $org2->setPassword($this->passwordHasher->hashPassword($org2, 'password123'));
        $org2->setEstado('pendiente');
        $org2->setSector($sectores[2]);
        $manager->persist($org2);

        $manager->flush();

        // --- 4. VOLUNTARIOS (ORM) ---
        echo "Cargando Voluntarios (ORM)...\n";
        for ($i = 0; $i < 10; $i++) {
            $vol = new \App\Entity\Voluntario();
            $dni = str_pad((string)$i, 8, '0', STR_PAD_LEFT) . chr(65 + $i);
            $vol->setDni($dni);
            $vol->setNombre($nombresVol[$i % count($nombresVol)]);
            $vol->setApellido1($apellidos[$i % count($apellidos)]);
            $vol->setApellido2($apellidos[($i + 1) % count($apellidos)]);
            $vol->setCorreo(strtolower($vol->getNombre() . '.' . $vol->getApellido1() . $i . '@email.com'));
            $vol->setPassword($this->passwordHasher->hashPassword($vol, 'password123'));
            $vol->setCoche(true);
            $vol->setFechaNacimiento(new \DateTime('2000-01-01'));
            $vol->setEstadoVoluntario($i % 3 === 0 ? 'PENDIENTE' : 'ACTIVO');
            $vol->setCiclo($ciclosEntities[$i % count($ciclosEntities)]);
            
            // Random Availability and Idioms
            $vDispo = [$disponibilidad[array_rand($disponibilidad)], $disponibilidad[array_rand($disponibilidad)]];
            $vol->setDisponibilidad(array_unique($vDispo));

            $vol->setIdiomas(['Español', 'Inglés']);
            
            // Random Relations
            $vol->addHabilidad($habilidadesEntities[$i % count($habilidadesEntities)]);
            $vol->addInterese($interesesEntities[$i % count($interesesEntities)]);
            
            $manager->persist($vol);
        }
        $manager->flush();

        // --- 5. ACTIVIDADES (ORM) ---
        echo "Cargando Actividades (ORM)...\n";
        $act1 = new \App\Entity\Actividad();
        $act1->setNombre('Reparto Alimentos');
        $act1->setDireccion('Almacen central');
        $act1->setFechaInicio(new \DateTime('2026-06-01 10:00:00'));
        $act1->setFechaFin(new \DateTime('2026-06-01 14:00:00'));
        $act1->setMaxParticipantes(10);
        $act1->setEstado('ABIERTA');
        $act1->setEstadoAprobacion('PENDIENTE');
        $act1->setOrganizacion($org1);
        $act1->addOd($odsEntities[0]);
        $act1->addHabilidad($habilidadesEntities[5]);
        $act1->addNecesidad($necesidadesEntities[0]);
        $manager->persist($act1);

        $act2 = new \App\Entity\Actividad();
        $act2->setNombre('Limpieza Playa');
        $act2->setDireccion('Playa del Sol');
        $act2->setFechaInicio(new \DateTime('2026-07-15 09:00:00'));
        $act2->setFechaFin(new \DateTime('2026-07-15 13:00:00'));
        $act2->setMaxParticipantes(50);
        $act2->setEstado('PENDIENTE');
        $act2->setEstadoAprobacion('PENDIENTE');
        $act2->setOrganizacion($org2);
        $act2->addOd($odsEntities[13]);
        $act2->addHabilidad($habilidadesEntities[2]);
        $act2->addNecesidad($necesidadesEntities[3]);
        $manager->persist($act2);

        $manager->flush();

        echo "Fixtures cargadas correctamente (ORM).\n";
    }
}

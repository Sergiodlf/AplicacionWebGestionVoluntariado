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
        
        $zonas = ['Pamplona', 'Tudela']; // Del combo box de Organizaciones
        
        $nombresVol = ['Carlos', 'Ana', 'Lucia', 'Marcos', 'Elena', 'Javier', 'Sara', 'David', 'Laura', 'Pablo'];
        $apellidos = ['Lopez', 'Martinez', 'Ruiz', 'Gomez', 'Fernandez', 'Garcia', 'Perez', 'Sanchez', 'Diaz', 'Rodríguez'];
        
        // --- 1. CICLOS (ORM works) ---
        echo "Cargando Ciclos Reales CIP Cuatrovientos (ORM)...\n";
        
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

        $ciclosEntities = [];

        foreach ($ciclosData as $cData) {
            $ciclo = new Ciclo();
            $ciclo->setCurso($cData['curso']);
            $ciclo->setNombre($cData['nombre']);
            $manager->persist($ciclo);
            $ciclosEntities[] = $ciclo->getNombre(); // Keep track for volunteers
        }
        $manager->flush();

        // --- 2. ORGANIZACIONES (ORM works) ---
        echo "Cargando Organizaciones (ORM)...\n";
        
        // Organización 1: Aprobada
        $org1 = new Organizacion();
        $org1->setCif('A12345678');
        $org1->setNombre('Cruz Roja');
        $org1->setDireccion('Calle Principal 123');
        $org1->setCp('28001');
        $org1->setLocalidad('Pamplona'); // Valid option
        $org1->setDescripcion('Ayuda humanitaria y servicios sociales.');
        $org1->setContacto('Juan Perez');
        $org1->setEmail('contacto@cruzroja.es');
        $org1->setPassword($this->passwordHasher->hashPassword($org1, 'password123'));
        $org1->setEstado('Aprobado');
        $org1->setSector($sectores[1]); // Salud
        $manager->persist($org1);

        // Organización 2: Pendiente
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
        $org2->setSector($sectores[2]); // Medio Ambiente
        $manager->persist($org2);
        
        // Organización 3: Aleatoria
        $org3 = new Organizacion();
        $org3->setCif('C11122233');
        $org3->setNombre('Asociación Vecinal');
        $org3->setDireccion('Plaza del Pueblo 1');
        $org3->setCp('31001');
        $org3->setLocalidad('Tudela');
        $org3->setDescripcion('Actividades para la comunidad local.');
        $org3->setContacto('Luis Torres');
        $org3->setEmail('vecinos@tudela.com');
        $org3->setPassword($this->passwordHasher->hashPassword($org3, 'password123'));
        $org3->setEstado('pendiente');
        $org3->setSector($sectores[3]); // Comunitario
        $manager->persist($org3);

        $manager->flush();

        // --- 3. VOLUNTARIOS (SQL for simplicity with Arrays) ---
        echo "Cargando Voluntarios (SQL)...\n";
        
        $passHash = $this->passwordHasher->hashPassword($org1, 'password123');
        $values = [];
        
        // Generar 10 voluntarios
        for ($i = 0; $i < 10; $i++) {
            $dni = str_pad((string)$i, 8, '0', STR_PAD_LEFT) . chr(65 + $i); // 00000000A, 00000001B...
            $nombre = $nombresVol[$i % count($nombresVol)];
            $apellido1 = $apellidos[$i % count($apellidos)];
            $apellido2 = $apellidos[($i + 1) % count($apellidos)];
            $email = strtolower($nombre . '.' . $apellido1 . '@email.com');
            $fechaNac = '200' . ($i % 5) . '0101'; // 2000-2004
            
            // Random selections from frontend data
            // JSON_UNESCAPED_UNICODE to ensure accents are stored correctly
            $misHabilidades = json_encode([$habilidades[$i % count($habilidades)], $habilidades[($i + 3) % count($habilidades)]], JSON_UNESCAPED_UNICODE);
            $misIntereses = json_encode([$intereses[$i % count($intereses)]], JSON_UNESCAPED_UNICODE);
            $miDisponibilidad = json_encode([$disponibilidad[$i % count($disponibilidad)]], JSON_UNESCAPED_UNICODE);
            
            $estado = ($i % 3 === 0) ? 'PENDIENTE' : 'ACTIVO';
            $curso = 2;
            // Assign random cycle from the list
            $ciclo = $ciclosEntities[$i % count($ciclosEntities)];
            
            $values[] = "('$dni', '$nombre', '$apellido1', '$apellido2', '$email', '$passHash', 1, '$fechaNac', '$estado', $curso, '$ciclo', '$misHabilidades', '$misIntereses', '$miDisponibilidad')";
        }

        $sqlVol = "INSERT INTO VOLUNTARIOS (DNI, NOMBRE, APELLIDO1, APELLIDO2, CORREO, PASSWORD, COCHE, FECHA_NACIMIENTO, ESTADO_VOLUNTARIO, CURSO_CICLOS, NOMBRE_CICLOS, HABILIDADES, INTERESES, DISPONIBILIDAD) VALUES " . implode(',', $values);
        
        $conn->executeStatement($sqlVol);

        // --- 4. ACTIVIDADES ---
        echo "Cargando Actividades (SQL)...\n";
        
        $ods1 = json_encode(['Fin de la pobreza'], JSON_UNESCAPED_UNICODE);
        $ods2 = json_encode(['Vida submarina'], JSON_UNESCAPED_UNICODE);
        
        $necesidades1 = json_encode(['Cocina', 'Manipulación de Alimentos'], JSON_UNESCAPED_UNICODE);
        $necesidades2 = json_encode(['Primeros Auxilios', 'Medio Ambiente'], JSON_UNESCAPED_UNICODE);

        $sqlAct = "INSERT INTO ACTIVIDADES (NOMBRE, DIRECCION, FECHA_INICIO, FECHA_FIN, MAX_PARTICIPANTES, ESTADO, CIF_EMPRESA, ODS, ESTADO_APROBACION, HABILIDADES) VALUES 
        ('Reparto Alimentos', 'Almacen central', '20260601 10:00:00', '20260601 14:00:00', 10, 'ABIERTA', 'A12345678', '$ods1', 'PENDIENTE', '$necesidades1'),
        ('Limpieza Playa', 'Playa del Sol', '20260715 09:00:00', '20260715 13:00:00', 50, 'PENDIENTE', 'B87654321', '$ods2', 'PENDIENTE', '$necesidades2')";

        $conn->executeStatement($sqlAct);

        // --- 5. INSCRIPCIONES ---
        echo "Cargando Inscripciones (SQL)...\n";
        // Fetch ID of first activity
        $idAct1 = $conn->fetchOne("SELECT TOP 1 CODACTIVIDAD FROM ACTIVIDADES WHERE NOMBRE = 'Reparto Alimentos'");
        
        if ($idAct1) {
            // Register first volunteer to first activity
             $conn->executeStatement("INSERT INTO INSCRIPCIONES (DNI_VOLUNTARIO, CODACTIVIDAD, ESTADO) VALUES ('00000000A', $idAct1, 'CONFIRMADA')");
        }

        echo "Fixtures cargadas correctamente (Mixto ORM/SQL).\n";
    }
}

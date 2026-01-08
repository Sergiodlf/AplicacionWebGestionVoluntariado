<?php

namespace App\DataFixtures;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Entity\Inscripcion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Organizaciones de ejemplo
        $org1 = new Organizacion();
        $org1->setCif('ORG123456A');
        $org1->setNombre('Asociación Buena Voluntad');
        $org1->setEmail('contacto@buenavoluntad.org');
        $org1->setPassword(password_hash('secret123', PASSWORD_BCRYPT));
        $org1->setSector('Social');
        $org1->setDireccion('Calle Mayor, 1');
        $org1->setLocalidad('Madrid');
        $org1->setDescripcion('Organización dedicada a ayudar a familias');
        $manager->persist($org1);

        $org2 = new Organizacion();
        $org2->setCif('ORG654321B');
        $org2->setNombre('Voluntarios Unidos');
        $org2->setEmail('info@voluntariosunidos.es');
        $org2->setPassword(password_hash('secret456', PASSWORD_BCRYPT));
        $org2->setSector('Educación');
        $org2->setDireccion('Avenida del Sol, 12');
        $org2->setLocalidad('Barcelona');
        $org2->setDescripcion('Promoción educativa y apoyo escolar');
        $manager->persist($org2);

        // Voluntarios de ejemplo
        $v1 = new Voluntario();
        $v1->setDni('11111111A');
        $v1->setNombre('María');
        $v1->setApellido1('García');
        $v1->setCorreo('maria.garcia@example.com');
        $v1->setPassword(password_hash('vol123', PASSWORD_BCRYPT));
        $v1->setZona('Madrid Centro');
        $manager->persist($v1);

        $v2 = new Voluntario();
        $v2->setDni('22222222B');
        $v2->setNombre('Juan');
        $v2->setApellido1('Pérez');
        $v2->setCorreo('juan.perez@example.com');
        $v2->setPassword(password_hash('vol456', PASSWORD_BCRYPT));
        $v2->setZona('Barcelona Norte');
        $manager->persist($v2);

        // Actividades de ejemplo
        $a1 = new Actividad();
        $a1->setNombre('Reparto de alimentos');
        $a1->setEstado('pendiente');
        $a1->setDescripcion('Reparto en barrio desfavorecido');
        $a1->setCifOrganizacion($org1->getCif());
        $manager->persist($a1);

        $a2 = new Actividad();
        $a2->setNombre('Clases de refuerzo');
        $a2->setEstado('pendiente');
        $a2->setDescripcion('Apoyo escolar para alumnos');
        $a2->setCifOrganizacion($org2->getCif());
        $manager->persist($a2);

        // Inscripciones de ejemplo
        $i1 = new Inscripcion();
        $i1->setActividad($a1);
        $i1->setVoluntario($v1);
        $i1->setFecha(new \DateTime());
        $manager->persist($i1);

        $i2 = new Inscripcion();
        $i2->setActividad($a2);
        $i2->setVoluntario($v2);
        $i2->setFecha(new \DateTime());
        $manager->persist($i2);

        $manager->flush();
    }
}
<?php

namespace App\DataFixtures;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
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
        // Organización de ejemplo
        $org = new Organizacion();
        $org->setCif('A12345678')
            ->setNombre('ONG Demo')
            ->setEmail('org@example.com')
            ->setPassword($this->passwordHasher->hashPassword($org, 'secret123'))
            ->setSector('Social')
            ->setDireccion('Calle Falsa 1')
            ->setLocalidad('Madrid')
            ->setCp('28001')
            ->setDescripcion('Organización de prueba')
            ->setContacto('Contacto Demo')
            ->setEstado('aceptada');
        $manager->persist($org);

        // Voluntario de ejemplo
        $vol = new Voluntario();
        $vol->setDni('12345678A')
            ->setNombre('Juan')
            ->setApellido1('Pérez')
            ->setApellido2('Gómez')
            ->setCorreo('juan@example.com')
            ->setPassword($this->passwordHasher->hashPassword($vol, 'secret123'))
            ->setZona('Madrid')
            ->setFechaNacimiento(new \DateTime('1990-01-01'))
            ->setExperiencia('Experiencia previa')
            ->setCoche(false)
            ->setHabilidades(json_encode(['cocina','ensenanza']))
            ->setIntereses(json_encode(['medioambiente']))
            ->setIdiomas(json_encode(['es','en']))
            ->setEstadoVoluntario('ACEPTADO');
        $manager->persist($vol);

        // Actividad de ejemplo
        $act = new Actividad();
        $act->setNombre('Reparto de alimentos')
            ->setDireccion('Plaza Mayor 1')
            ->setFechaInicio(new \DateTime('+7 days'))
            ->setFechaFin(new \DateTime('+8 days'))
            ->setMaxParticipantes(20)
            ->setOds(['1','2'])
            ->setHabilidades(['organizacion','transporte'])
            ->setOrganizacion($org);
        $manager->persist($act);

        // Inscripción de ejemplo
        $ins = new Inscripcion();
        $ins->setVoluntario($vol)
            ->setActividad($act)
            ->setEstado('CONFIRMADA');
        $manager->persist($ins);

        $manager->flush();
    }
}


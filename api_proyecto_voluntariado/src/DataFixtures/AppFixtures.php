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
<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}

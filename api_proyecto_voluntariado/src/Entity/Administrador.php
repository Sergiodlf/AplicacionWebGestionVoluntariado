use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdministradorRepository::class)]
class Administrador implements Loginable, Notifiable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];



    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $nombre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_ADMIN';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }




    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    // --- Loginable ---
    public function canLogin(): bool
    {
        return true; // Admins are always allowed if they exist in DB
    }

    public function getLoginDeniedReason(): ?string
    {
        return null;
    }

    // --- Notifiable ---
    public function getNotifiableEmail(): string
    {
        return (string) $this->email;
    }

    public function getFcmToken(): ?string
    {
        return null; // Admins don't have personal tokens for now
    }
}

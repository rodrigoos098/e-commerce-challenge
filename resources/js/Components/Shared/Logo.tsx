interface LogoProps {
  className?: string;
}

export default function Logo({ className = 'h-10 sm:h-12 w-auto' }: LogoProps) {
  return <img src="/logo.svg" alt="Shopsugi Logo" className={className} />;
}

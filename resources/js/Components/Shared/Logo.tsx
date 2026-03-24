interface LogoProps {
    className?: string;
}

export default function Logo({ className = 'text-2xl font-display font-extrabold text-warm-700' }: LogoProps) {
    return (
        <span className={className}>
            Shopsugi<span className="text-kintsugi-600">ツ</span>
        </span>
    );
}

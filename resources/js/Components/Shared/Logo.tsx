interface LogoProps {
    className?: string;
}

export default function Logo({ className = 'text-2xl font-display font-extrabold tracking-tight' }: LogoProps) {
    return (
        <span className={`${className} text-warm-800`}>
            Shopsugi<span className="text-kintsugi-500 ml-0.5">ツ</span>
        </span>
    );
}

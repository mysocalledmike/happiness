import { Star } from 'lucide-react';

interface LogoProps {
  className?: string;
  onBanner?: boolean; // New prop to indicate if logo is on a colored banner
}

export function Logo({ className = '', onBanner = false }: LogoProps) {
  const text = '1 Trillion Smiles';

  // Render text with gradient styling (unless on banner where we keep it white)
  const renderStyledText = () => {
    if (onBanner) {
      return <>{text}</>;
    }
    
    return (
      <span className="bg-gradient-to-br from-yellow-300 to-orange-300 bg-clip-text text-transparent">
        {text}
      </span>
    );
  };

  return (
    <div className={`${className}`}>
      <div className="relative inline-block">
        <h1 className={`text-2xl font-bold text-white pb-3 font-poppins`}>
          {renderStyledText()}
        </h1>
        <div className="absolute bottom-0 left-0 right-0 flex items-center gap-1">
          <div className="flex-1 h-0.5 bg-gradient-to-r from-white/0 via-white/60 to-white/0 rounded-full"></div>
          <div className="text-white text-xs">✨</div>
          <div className="flex-1 h-0.5 bg-gradient-to-r from-white/0 via-white/60 to-white/0 rounded-full"></div>
        </div>
      </div>
    </div>
  );
}

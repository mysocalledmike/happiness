import { X, Globe } from 'lucide-react';
import { GLOBAL_SMILES, GLOBAL_GOAL } from '../constants';

interface GlobalProgressModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function GlobalProgressModal({ isOpen, onClose }: GlobalProgressModalProps) {
  if (!isOpen) return null;

  const globalProgress = (GLOBAL_SMILES / GLOBAL_GOAL) * 100;
  
  // Calculate estimated days to reach 1 trillion
  // Assuming current rate continues (this is just for fun)
  const smilesPerDay = 50; // Mock rate - would be calculated from real data
  const smilesRemaining = GLOBAL_GOAL - GLOBAL_SMILES;
  const daysRemaining = Math.ceil(smilesRemaining / smilesPerDay);
  const yearsRemaining = Math.floor(daysRemaining / 365);

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onClick={onClose}>
      <div className="bg-white rounded-3xl p-8 max-w-2xl w-full shadow-2xl relative" onClick={(e) => e.stopPropagation()}>
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
        >
          <X className="w-6 h-6" />
        </button>
        <div className="flex items-center gap-3 mb-6">
          <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
            <Globe className="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 className="text-3xl font-bold text-gray-900 font-poppins">Global Progress</h3>
            <p className="text-gray-600 font-lora">Our journey to 1 trillion smiles</p>
          </div>
        </div>
        <div className="space-y-6">
          <div className="text-6xl font-bold bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent font-poppins">
            {GLOBAL_SMILES.toLocaleString()}
          </div>
          <div className="h-6 bg-gray-100 rounded-full overflow-hidden">
            <div
              className="h-full bg-gradient-to-r from-orange-400 to-pink-500 rounded-full transition-all"
              style={{ width: `${Math.min(globalProgress * 1000, 100)}%` }}
            />
          </div>
          <div className="space-y-2">
            <p className="text-2xl font-semibold text-gray-900 font-poppins">
              {(globalProgress).toFixed(6)}% of 1 trillion
            </p>
            <p className="text-gray-600 font-lora">
              Every smile counts. Together, we're spreading happiness one heartfelt message at a time.
            </p>
          </div>
          <div className="pt-4 border-t border-gray-200">
            <div className="text-center space-y-2">
              <p className="text-xs text-gray-500 font-lora">
                At our current pace, we'll reach 1 trillion smiles in...
              </p>
              <p className="text-3xl font-bold bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent font-poppins">
                ~{yearsRemaining.toLocaleString()} years 🚀
              </p>
              <p className="text-xs text-gray-500 italic font-lora">
                (But we think we can do better!)
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

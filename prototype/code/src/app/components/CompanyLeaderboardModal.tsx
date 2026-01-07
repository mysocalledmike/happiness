import { X, TrendingUp } from 'lucide-react';
import { COMPANY_NAME, COMPANY_SMILES, TOP_SENDERS } from '../constants';

interface CompanyLeaderboardModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function CompanyLeaderboardModal({ isOpen, onClose }: CompanyLeaderboardModalProps) {
  if (!isOpen) return null;

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
            <TrendingUp className="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 className="text-3xl font-bold text-gray-900 font-poppins">{COMPANY_SMILES} {COMPANY_NAME} smiles</h3>
            <p className="text-gray-600 font-lora">from {COMPANY_NAME} employees</p>
          </div>
        </div>
        <div className="space-y-3">
          {TOP_SENDERS.map((sender, index) => (
            <div
              key={sender.name}
              className="flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-orange-50 to-pink-50"
            >
              <div className="text-2xl font-bold text-orange-400 w-8 font-poppins">
                #{index + 1}
              </div>
              <div className="text-3xl">{sender.character}</div>
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-gray-900 text-lg truncate font-poppins">{sender.name}</div>
                <div className="text-sm text-gray-600 font-lora">
                  {sender.smiles} smiles created
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-6 p-4 bg-orange-50 rounded-xl border border-orange-200">
          <p className="text-sm text-gray-700 text-center font-lora">
            💡 <span className="font-semibold">Send more smiles to create a happier {COMPANY_NAME}</span>
          </p>
        </div>
      </div>
    </div>
  );
}
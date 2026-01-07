import { useState } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { TrendingUp, X, Globe, Building2 } from 'lucide-react';
import { Logo } from './Logo';
import { GlobalProgressModal } from './GlobalProgressModal';
import { CompanyLeaderboardModal } from './CompanyLeaderboardModal';
import { GLOBAL_SMILES, GLOBAL_GOAL, COMPANY_NAME, COMPANY_SMILES, TOTAL_COMPANIES, TOP_COMPANIES, TOP_SENDERS } from '../constants';

interface HomePageProps {
  onGetStarted: (userData: { name: string; email: string; character: string }) => void;
}

const characters = ['🌟', '🌈', '☀️', '🎈', '🌸', '🎨', '🎪', '🎭', '🦄', '🌺'];

export function HomePage({ onGetStarted }: HomePageProps) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [selectedCharacter, setSelectedCharacter] = useState('');
  const [showLeaderboard, setShowLeaderboard] = useState(false);
  const [showProgress, setShowProgress] = useState(false);
  const [showCompanies, setShowCompanies] = useState(false);

  const globalProgress = (GLOBAL_SMILES / GLOBAL_GOAL) * 100;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (name && email && selectedCharacter) {
      onGetStarted({ name, email, character: selectedCharacter });
    }
  };

  const scrollToProgress = () => {
    const element = document.getElementById('progress-section');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="min-h-screen">
      {/* SECTION 1: GET STARTED - Split hero with inline signup */}
      <div className="bg-gradient-to-r from-orange-500 via-pink-500 to-orange-500">
        <div className="max-w-6xl mx-auto px-6 py-12 md:py-16">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            {/* Left: Logo + Tagline + Copy + Stats */}
            <div className="text-white space-y-6">
              {/* Logo lockup */}
              <Logo />
              
              {/* Hero tagline */}
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold font-poppins">
                Spread Smiles,<br />Not status
              </h1>
              
              {/* Tagline */}
              <div>
                <p className="text-lg md:text-xl font-lora opacity-90">
                  Smiles are heartfelt messages you send that create happiness and make people's day.
                </p>
              </div>
              
              {/* Three small bullets */}
              <div className="space-y-3 pt-4">
                <button
                  onClick={scrollToProgress}
                  className="flex items-center gap-3 text-white/90 hover:text-white transition-colors group"
                >
                  <Globe className="w-5 h-5" />
                  <span className="text-sm">
                    <span className="font-bold">{GLOBAL_SMILES.toLocaleString()}</span> of 1T smiles spread worldwide
                  </span>
                </button>
                <button
                  onClick={scrollToProgress}
                  className="flex items-center gap-3 text-white/90 hover:text-white transition-colors group"
                >
                  <Building2 className="w-5 h-5" />
                  <span className="text-sm">
                    <span className="font-bold">{TOTAL_COMPANIES}</span> companies spreading smiles
                  </span>
                </button>
                <button
                  onClick={scrollToProgress}
                  className="flex items-center gap-3 text-white/90 hover:text-white transition-colors group"
                >
                  <TrendingUp className="w-5 h-5" />
                  <span className="text-sm">
                    <span className="font-bold">{COMPANY_SMILES}</span> smiles spread at {COMPANY_NAME}
                  </span>
                </button>
              </div>
            </div>

            {/* Right: Signup Form (always visible) */}
            <div>
              <form onSubmit={handleSubmit} className="bg-white rounded-3xl p-8 shadow-2xl space-y-6">
                <h2 className="text-2xl font-bold text-gray-900 font-poppins">Get Started</h2>
                
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm text-gray-700 mb-2 font-lora">Your name</label>
                    <Input
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      placeholder="Alex"
                      className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm text-gray-700 mb-2 font-lora">Your email</label>
                    <Input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="alex@example.com"
                      className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm text-gray-700 mb-2 font-lora">Your avatar</label>
                    <div className="grid grid-cols-5 gap-2">{characters.map((char) => (
                        <button
                          key={char}
                          type="button"
                          onClick={() => setSelectedCharacter(char)}
                          className={`text-3xl p-3 rounded-xl transition-all ${
                            selectedCharacter === char
                              ? 'bg-gradient-to-br from-orange-400 to-pink-500 scale-110 shadow-lg'
                              : 'bg-gray-50 hover:bg-gray-100'
                          }`}
                        >
                          {char}
                        </button>
                      ))}</div>
                  </div>
                </div>

                <Button
                  type="submit"
                  disabled={!name || !email || !selectedCharacter}
                  className="w-full bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white py-3 rounded-full font-poppins"
                >
                  Start Spreading Smiles
                </Button>
              </form>
            </div>
          </div>
        </div>
      </div>

      {/* SECTION 2: LEARN MORE - WHY → HOW WE'RE DOING */}
      <div className="bg-gradient-to-br from-orange-50 via-pink-50 to-yellow-50">
        <div className="max-w-6xl mx-auto px-6 py-16 md:py-20 space-y-20">
          
          {/* WHY WE'RE DOING THIS */}
          <div className="space-y-8">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 font-poppins">
              The Internet is making us miserable, so let's fix it.
            </h2>
            
            <div className="grid md:grid-cols-2 gap-8">
              <div className="bg-white rounded-3xl p-8 shadow-lg relative overflow-hidden">
                <div className="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full blur-3xl opacity-30"></div>
                <div className="relative z-10">
                  <div className="text-6xl mb-6">😔</div>
                  <p className="text-lg text-gray-700 leading-relaxed font-lora">
                    Between endless scrolling, comparing our lives to highlight reels, or chasing likes and followers that don't actually make us happy, depression rates among young people have doubled over the past decade.
                  </p>
                </div>
              </div>
              <div className="bg-white rounded-3xl p-8 shadow-lg relative overflow-hidden">
                <div className="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-200 to-pink-200 rounded-full blur-3xl opacity-30"></div>
                <div className="relative z-10">
                  <div className="text-6xl mb-6">😊</div>
                  <p className="text-lg text-gray-700 leading-relaxed font-lora">
                    So instead of vanity metrics, shallow self-promotion, or algorithms that make you feel inadequate, focus on what really makes us happy - kindness, gratitude, and the simple joy of making someone's day better.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* HOW WE'RE DOING - Three cards */}
          <div id="progress-section" className="space-y-6">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 font-poppins">
              Mission: Create 1 trillion smiles, one at a time.
            </h2>
            <p className="text-xl text-gray-600 mb-8 font-lora">
              Send smiles to the people who make your day, workplace, or entire life better, and join the movement to make the Internet a little brighter.
            </p>

            <div className="grid md:grid-cols-3 gap-6">
              {/* Global Counter */}
              <button
                onClick={() => setShowProgress(true)}
                className="bg-white rounded-3xl p-8 shadow-lg text-left hover:shadow-xl transition-shadow flex flex-col cursor-pointer"
              >
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
                    <Globe className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">Total Smiles</h3>
                    <p className="text-sm text-gray-600">created globally</p>
                  </div>
                </div>
                <div className="space-y-4">
                  <div className="text-5xl font-bold bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent">
                    {GLOBAL_SMILES.toLocaleString()}
                  </div>
                  <div className="h-4 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-gradient-to-r from-orange-400 to-pink-500 rounded-full transition-all"
                      style={{ width: `${Math.min(globalProgress * 1000, 100)}%` }}
                    />
                  </div>
                  <p className="text-sm text-gray-600">
                    {(globalProgress).toFixed(6)}% of 1 trillion
                  </p>
                </div>
              </button>

              {/* Companies Involved */}
              <button
                onClick={() => setShowCompanies(true)}
                className="bg-white rounded-3xl p-8 shadow-lg text-left hover:shadow-xl transition-shadow flex flex-col cursor-pointer"
              >
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
                    <Building2 className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">{TOTAL_COMPANIES} companies</h3>
                    <p className="text-sm text-gray-600">spreading smiles</p>
                  </div>
                </div>
                <div className="space-y-2">
                  {TOP_COMPANIES.slice(0, 3).map((company, index) => (
                    <div
                      key={company.name}
                      className="flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-orange-50 to-pink-50"
                    >
                      <div className="text-lg font-bold text-orange-400 w-6">
                        #{index + 1}
                      </div>
                      <div className="text-xl">
                        <Building2 className="w-5 h-5 text-gray-600" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="font-semibold text-gray-900 truncate text-sm">{company.name}</div>
                        <div className="text-xs text-gray-600">
                          {company.smiles} smiles created
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </button>

              {/* Company Leaderboard */}
              <button
                onClick={() => setShowLeaderboard(true)}
                className="bg-white rounded-3xl p-8 shadow-lg text-left hover:shadow-xl transition-shadow flex flex-col cursor-pointer"
              >
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
                    <TrendingUp className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">{COMPANY_SMILES} {COMPANY_NAME} smiles</h3>
                    <p className="text-sm text-gray-600">from {COMPANY_NAME} employees</p>
                  </div>
                </div>
                <div className="space-y-2">
                  {TOP_SENDERS.slice(0, 3).map((sender, index) => (
                    <div
                      key={sender.name}
                      className="flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-orange-50 to-pink-50"
                    >
                      <div className="text-lg font-bold text-orange-400 w-6">
                        #{index + 1}
                      </div>
                      <div className="text-xl">{sender.character}</div>
                      <div className="flex-1 min-w-0">
                        <div className="font-semibold text-gray-900 truncate text-sm">{sender.name}</div>
                        <div className="text-xs text-gray-600">
                          {sender.smiles} smiles created
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </button>
            </div>
          </div>

        </div>
      </div>

      {/* Progress Modal */}
      <GlobalProgressModal
        isOpen={showProgress}
        onClose={() => setShowProgress(false)}
      />

      {/* Leaderboard Modal */}
      <CompanyLeaderboardModal
        isOpen={showLeaderboard}
        onClose={() => setShowLeaderboard(false)}
      />

      {/* Companies Modal */}
      {showCompanies && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onClick={() => setShowCompanies(false)}>
          <div className="bg-white rounded-3xl p-8 max-w-2xl w-full shadow-2xl relative" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={() => setShowCompanies(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
              <X className="w-6 h-6" />
            </button>
            <div className="flex items-center gap-3 mb-6">
              <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
                <Building2 className="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 className="text-3xl font-bold text-gray-900 font-poppins">{TOTAL_COMPANIES} companies</h3>
                <p className="text-gray-600 font-lora">spreading smiles</p>
              </div>
            </div>
            <div className="space-y-3">
              {TOP_COMPANIES.map((company, index) => (
                <div
                  key={company.name}
                  className="flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-orange-50 to-pink-50"
                >
                  <div className="text-2xl font-bold text-orange-400 w-8 font-poppins">
                    #{index + 1}
                  </div>
                  <div className="text-2xl">
                    <Building2 className="w-6 h-6 text-gray-600" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="font-semibold text-gray-900 text-lg truncate font-poppins">{company.name}</div>
                    <div className="text-sm text-gray-600 font-lora">
                      {company.smiles} smiles created
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="mt-6 p-4 bg-orange-50 rounded-xl border border-orange-200">
              <p className="text-sm text-gray-700 text-left font-lora">
                💡 <span className="font-semibold">Share this with friends at other companies to make their work day a little bright, too!</span>
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
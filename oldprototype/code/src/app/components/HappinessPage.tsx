import { useState, useRef, useEffect } from "react";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import {
  Search,
  TrendingUp,
  X,
  Globe,
  Sparkles,
} from "lucide-react";
import { Logo } from "./Logo";
import { GlobalProgressModal } from "./GlobalProgressModal";
import { CompanyLeaderboardModal } from "./CompanyLeaderboardModal";
import { MessageCard } from "./MessageCard";
import {
  GLOBAL_SMILES,
  GLOBAL_GOAL,
  COMPANY_NAME,
  COMPANY_SMILES,
} from "../constants";
import { motion } from "motion/react";

interface HappinessPageProps {
  sender: {
    name: string;
    character: string;
    totalSmiles: number;
  };
  receiverEmail: string;
  receiverName?: string;
  message: string;
  onCreateOwn: () => void;
}

// Mock data - would come from props in real app
const TOP_SENDERS = [
  { name: "Sarah Chen", character: "🌟", smiles: 42 },
  { name: "Mike Rodriguez", character: "🌈", smiles: 38 },
  { name: "Priya Patel", character: "☀️", smiles: 35 },
  { name: "Alex Kim", character: "🎨", smiles: 31 },
  { name: "Jordan Lee", character: "🌸", smiles: 28 },
];

export function HappinessPage({
  sender,
  receiverEmail,
  receiverName,
  message,
  onCreateOwn,
}: HappinessPageProps) {
  const [lookupEmail, setLookupEmail] = useState("");
  const [lookupResult, setLookupResult] = useState<
    string | null
  >(null);
  const [showLeaderboard, setShowLeaderboard] = useState(false);
  const [showProgress, setShowProgress] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [signupName, setSignupName] = useState("");
  const [signupEmail, setSignupEmail] = useState("");
  const [signupCharacter, setSignupCharacter] = useState("");
  const [hasSmiled, setHasSmiled] = useState(false);
  const [senderSmileCount, setSenderSmileCount] = useState(
    sender.totalSmiles,
  );
  const signupBoxRef = useRef<HTMLDivElement>(null);

  const globalProgress = (GLOBAL_SMILES / GLOBAL_GOAL) * 100;
  const characters = [
    "🌟",
    "🌈",
    "☀️",
    "🎈",
    "🌸",
    "🎨",
    "🎪",
    "🎭",
    "🦋",
    "🌺",
  ];

  // Mock data for demonstration
  const mockMessages: Record<string, string> = {
    "sarah@example.com":
      "Thank you for always being there when I needed help on the project! Your positive energy made such a difference.",
    "mike@example.com":
      "I really appreciated your feedback during the presentation. You helped me see things from a new perspective!",
    "jen@example.com":
      "Your kindness during a tough week meant the world to me. Thank you for being such a great colleague!",
  };

  // Infer emoji from message content
  const inferEmoji = (text: string): string => {
    const lowerText = text.toLowerCase();
    if (
      lowerText.includes("thank") ||
      lowerText.includes("grateful")
    )
      return "🙏";
    if (
      lowerText.includes("love") ||
      lowerText.includes("heart")
    )
      return "💛";
    if (
      lowerText.includes("great") ||
      lowerText.includes("amazing") ||
      lowerText.includes("wonderful") ||
      lowerText.includes("incredible")
    )
      return "⭐";
    if (
      lowerText.includes("help") ||
      lowerText.includes("support")
    )
      return "🤝";
    if (
      lowerText.includes("happy") ||
      lowerText.includes("joy")
    )
      return "😊";
    if (
      lowerText.includes("creative") ||
      lowerText.includes("idea")
    )
      return "💡";
    if (
      lowerText.includes("positive") ||
      lowerText.includes("energy")
    )
      return "✨";
    if (
      lowerText.includes("kind") ||
      lowerText.includes("care")
    )
      return "💕";
    return "😊"; // default
  };

  const messageEmoji = inferEmoji(message);

  const handleLookup = () => {
    if (lookupEmail in mockMessages) {
      setLookupResult(mockMessages[lookupEmail]);
    } else {
      setLookupResult("no-message");
    }
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (signupName && signupEmail && signupCharacter) {
      // In real app, this would trigger signup
      setShowCreateModal(false);
      onCreateOwn();
    }
  };

  const handleSmileClick = () => {
    if (!hasSmiled) {
      setHasSmiled(true);
      setSenderSmileCount(senderSmileCount + 1);
      // In real app, this would increment the sender's smile counter
      console.log("Smile count incremented for", sender.name);
    }
  };

  // Scroll to signup box when it appears
  useEffect(() => {
    if (hasSmiled && signupBoxRef.current) {
      setTimeout(() => {
        signupBoxRef.current?.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
      }, 100);
    }
  }, [hasSmiled]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 via-pink-50 to-yellow-50">
      {/* Combined Sticky Header + Banner (matching dashboard) */}
      <div className="sticky top-0 z-40 bg-gradient-to-r from-orange-500 via-pink-500 to-orange-500 shadow-lg">
        <div className="max-w-6xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between gap-4">
            <a
              href="/"
              className="hover:opacity-80 transition-opacity"
            >
              <Logo onBanner={true} />
            </a>
            <div className="flex items-center gap-4">
              <button
                onClick={() => setShowProgress(true)}
                className="hidden sm:flex items-center gap-2 text-white/90 hover:text-white transition-colors font-lora"
              >
                <Globe className="w-4 h-4" />
                <span className="text-sm">
                  <span className="font-bold">
                    {GLOBAL_SMILES.toLocaleString()}
                  </span>{" "}
                  of 1T smiles
                </span>
              </button>
              <button
                onClick={() => setShowLeaderboard(true)}
                className="hidden sm:flex items-center gap-2 text-white/90 hover:text-white transition-colors font-lora"
              >
                <TrendingUp className="w-4 h-4" />
                <span className="text-sm">
                  <span className="font-bold">
                    {COMPANY_SMILES}
                  </span>{" "}
                  {COMPANY_NAME} smiles
                </span>
              </button>
              <Button
                onClick={() => setShowCreateModal(true)}
                className="bg-white text-orange-600 hover:bg-orange-50 px-4 py-2 rounded-full text-sm"
              >
                Start Spreading Smiles
              </Button>
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

      {/* Create Modal */}
      {showCreateModal && (
        <div
          className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          onClick={() => setShowCreateModal(false)}
        >
          <div
            className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl relative"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              onClick={() => setShowCreateModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
              <X className="w-6 h-6" />
            </button>
            <h2 className="text-2xl font-bold text-gray-900 mb-2 font-poppins">
              Make work a little happier
            </h2>
            <p className="text-gray-600 mb-6 font-lora">
              Bring a smile to a coworker's face by sending kind
              messages - it'll make their day and make you
              happier, too.
            </p>
            <form
              onSubmit={handleCreateSubmit}
              className="space-y-4"
            >
              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">
                  Your name
                </label>
                <Input
                  value={signupName}
                  onChange={(e) =>
                    setSignupName(e.target.value)
                  }
                  placeholder="Alex"
                  className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                  required
                />
              </div>

              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">
                  Your email
                </label>
                <Input
                  type="email"
                  value={signupEmail}
                  onChange={(e) =>
                    setSignupEmail(e.target.value)
                  }
                  placeholder="alex@example.com"
                  className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                  required
                />
              </div>

              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">
                  Pick your vibe
                </label>
                <div className="grid grid-cols-5 gap-2">
                  {characters.map((char) => (
                    <button
                      key={char}
                      type="button"
                      onClick={() => setSignupCharacter(char)}
                      className={`text-3xl p-3 rounded-xl transition-all ${
                        signupCharacter === char
                          ? "bg-gradient-to-br from-orange-400 to-pink-500 scale-110 shadow-lg"
                          : "bg-gray-50 hover:bg-gray-100"
                      }`}
                    >
                      {char}
                    </button>
                  ))}
                </div>
              </div>

              <Button
                type="submit"
                disabled={
                  !signupName ||
                  !signupEmail ||
                  !signupCharacter
                }
                className="w-full bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white py-3 rounded-full font-poppins"
              >
                Start Spreading Smiles
              </Button>
            </form>
          </div>
        </div>
      )}

      <div className="max-w-3xl mx-auto px-6 py-16">
        {/* Message Card - THE HERO */}
        <div className="mb-8">
          <MessageCard
            senderName={sender.name}
            senderCharacter={sender.character}
            totalSmiles={senderSmileCount}
            message={message}
            recipientEmail={receiverEmail}
            recipientName={receiverName}
          />
        </div>

        {/* This Made Me Smile Button - PROMINENT */}
        {!hasSmiled ? (
          <div className="mb-12 flex justify-center">
            <Button
              onClick={handleSmileClick}
              className="px-16 py-8 rounded-full text-2xl font-bold transition-all font-poppins bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white shadow-2xl hover:shadow-3xl hover:scale-110"
            >
              <span className="mr-3 text-3xl">☺</span>
              {sender.name} made me smile
            </Button>
          </div>
        ) : (
          /* Signup Form - Shows after clicking the smile button */
          <motion.div
            ref={signupBoxRef}
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, ease: "easeOut" }}
            className="mb-12 space-y-6"
          >
            {/* Confirmation Message - Above the box */}
            <div className="text-center">
              <div className="inline-block bg-orange-50 px-6 py-4 rounded-2xl border border-orange-100">
                <p className="text-lg text-gray-800 font-lora">
                  <span className="font-semibold text-orange-600">
                    {sender.name}
                  </span>{" "}
                  made you smile, so we added to their smile
                  count.
                </p>
              </div>
            </div>

            {/* Signup Box */}
            <div className="bg-white rounded-3xl p-8 shadow-2xl">
              <h2 className="text-2xl font-bold text-gray-900 mb-2 font-poppins">
                Make work a little happier
              </h2>
              <p className="text-gray-600 mb-6 font-lora">
                Bring a smile to a coworker's face by sending
                kind messages - it'll make their day and make
                you happier, too.
              </p>
              <form
                onSubmit={handleCreateSubmit}
                className="space-y-4"
              >
                <div>
                  <label className="block text-sm text-gray-700 mb-2 font-lora">
                    Your name
                  </label>
                  <Input
                    value={signupName}
                    onChange={(e) =>
                      setSignupName(e.target.value)
                    }
                    placeholder="Alex"
                    className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-2 font-lora">
                    Your email
                  </label>
                  <Input
                    type="email"
                    value={signupEmail}
                    onChange={(e) =>
                      setSignupEmail(e.target.value)
                    }
                    placeholder="alex@example.com"
                    className="border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-2 font-lora">
                    Pick your vibe
                  </label>
                  <div className="grid grid-cols-5 gap-2">
                    {characters.map((char) => (
                      <button
                        key={char}
                        type="button"
                        onClick={() => setSignupCharacter(char)}
                        className={`text-3xl p-3 rounded-xl transition-all ${
                          signupCharacter === char
                            ? "bg-gradient-to-br from-orange-400 to-pink-500 scale-110 shadow-lg"
                            : "bg-gray-50 hover:bg-gray-100"
                        }`}
                      >
                        {char}
                      </button>
                    ))}
                  </div>
                </div>

                <Button
                  type="submit"
                  disabled={
                    !signupName ||
                    !signupEmail ||
                    !signupCharacter
                  }
                  className="w-full bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white py-3 rounded-full font-poppins"
                >
                  Start Spreading Smiles
                </Button>
              </form>
            </div>
          </motion.div>
        )}
      </div>
    </div>
  );
}
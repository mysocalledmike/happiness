import {
  Sparkles,
  Trash2,
  Send,
  Smile,
  Eye,
} from "lucide-react";
import { Button } from "./ui/button";

interface MessageCardProps {
  senderName: string;
  senderCharacter: string;
  totalSmiles: number;
  message: string;
  recipientEmail: string;
  recipientName?: string;
  // Optional overlay props for sent messages
  sentDate?: string;
  read?: boolean;
  readDate?: string;
  onPreview?: () => void;
  onDelete?: () => void;
}

export function MessageCard({
  senderName,
  senderCharacter,
  totalSmiles,
  message,
  recipientEmail,
  recipientName,
  sentDate,
  read,
  readDate,
  onPreview,
  onDelete,
}: MessageCardProps) {
  const isSentMessage = !!(sentDate || onPreview || onDelete);

  // Get emoji to display next to message
  const messageEmoji = "💛";

  return (
    <div className="relative bg-white rounded-3xl p-8 md:p-12 shadow-lg">
      {/* Management Overlay - Only for sent messages */}
      {isSentMessage && (
        <div className="absolute top-4 right-4 flex items-center gap-2">
          {/* Read/Sent Status Badge */}
          {read && readDate ? (
            <span className="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full font-semibold font-lora flex items-center gap-1.5">
              <Smile className="w-3.5 h-3.5 text-gray-500" />
              Smile created {readDate}
            </span>
          ) : sentDate ? (
            <span className="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full font-semibold font-lora flex items-center gap-1.5">
              <Send className="w-3.5 h-3.5 text-gray-500" />
              Sent {sentDate}
            </span>
          ) : null}

          {onPreview && (
            <Button
              onClick={onPreview}
              size="sm"
              variant="ghost"
              className="text-gray-400 hover:text-orange-500 hover:bg-orange-50"
              title="View as receiver"
            >
              <Eye className="w-4 h-4" />
            </Button>
          )}

          {onDelete && (
            <Button
              onClick={onDelete}
              size="sm"
              variant="ghost"
              className="text-gray-400 hover:text-red-500 hover:bg-red-50"
              title="Delete message"
            >
              <Trash2 className="w-4 h-4" />
            </Button>
          )}
        </div>
      )}

      <div className="relative">
        {/* For you badge with inline email */}
        <div className="mb-6">
          <div className="inline-flex items-center gap-2 bg-gradient-to-r from-orange-100 to-pink-100 px-4 py-2 rounded-full">
            <Sparkles className="w-4 h-4 text-orange-600" />
            <span className="text-sm font-semibold text-orange-900">
              Hey{" "}
              {recipientName || recipientEmail.split("@")[0]}
              {isSentMessage && (
                <span className="text-gray-500 font-normal ml-1">
                  · {recipientEmail}
                </span>
              )}
            </span>
          </div>
        </div>

        {/* The Message with emoji accent */}
        <div
          className={
            isSentMessage
              ? "flex items-start gap-4"
              : "mb-8 flex items-start gap-4"
          }
        >
          <div className="text-4xl flex-shrink-0 mt-2">
            {messageEmoji}
          </div>
          <div className="flex-1">
            <p className="text-3xl md:text-4xl text-gray-900 leading-relaxed font-lora">
              "{message}"
            </p>
          </div>
        </div>

        {/* Sender info - Only show for non-sent messages (preview mode) */}
        {!isSentMessage && (
          <div className="flex items-center gap-3 pt-6 border-t border-gray-200">
            <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center text-2xl">
              {senderCharacter}
            </div>
            <div>
              <p className="font-semibold text-gray-900 font-poppins">
                {senderName}
              </p>
              <p className="text-sm text-gray-600 font-lora">
                Has created {totalSmiles} smile
                {totalSmiles !== 1 ? "s" : ""} ✨
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
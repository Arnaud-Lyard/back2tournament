import type {
  GamePlayer,
  Player,
  PostPlayer200,
} from "@/libs/api/generated/endpoints.schemas"

export type { GamePlayer }

export type CreatedPlayer = PostPlayer200

export type UpdatedPlayer = Player

export type DeletedPlayer = Player

export const PLAYERS_PER_PAGE = 12

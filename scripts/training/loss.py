import torch
import torch.nn as nn
import torch.nn.functional as F


class GE2ELoss(nn.Module):
    """
    Generalized End-to-End Loss for Speaker Verification
    Reference: https://arxiv.org/abs/1710.10467
    
    Trains the model to maximize similarity between same-speaker embeddings
    and minimize similarity between different-speaker embeddings.
    """
    def __init__(self, init_w=10.0, init_b=-5.0, loss_method='softmax'):
        super(GE2ELoss, self).__init__()
        self.w = nn.Parameter(torch.tensor(init_w))
        self.b = nn.Parameter(torch.tensor(init_b))
        self.loss_method = loss_method
        assert self.loss_method in ['softmax', 'contrast']

    def forward(self, embeddings, label=None):
        """
        Args:
            embeddings: (N_speakers, M_utterances, D_embedding)
        Returns:
            loss: scalar
        """
        torch.clamp(self.w, 1e-6)
        
        N, M, D = embeddings.shape
        
        # L2 normalize embeddings
        embeddings = F.normalize(embeddings, p=2, dim=2)
        
        # Centroids per speaker: (N, D)
        centroids = torch.mean(embeddings, dim=1)  # (N, D)
        centroids = F.normalize(centroids, p=2, dim=1)
        
        # Flatten embeddings: (N*M, D)
        embeddings_flat = embeddings.reshape(N * M, D)
        
        # Compute similarity matrix: (N*M, N)
        # Each row = one utterance, each col = one speaker centroid
        sim_matrix = torch.matmul(embeddings_flat, centroids.t())  # (N*M, N)
        
        # Reshape to (N, M, N) for per-speaker processing
        sim_matrix = sim_matrix.reshape(N, M, N)
        
        # Scale and shift
        sim_matrix = self.w * sim_matrix + self.b
        
        if self.loss_method == 'softmax':
            # For each utterance, softmax cross-entropy with correct speaker
            # Target: each utterance j,i should match speaker j
            sim_flat = sim_matrix.reshape(N * M, N)  # (N*M, N)
            targets = torch.arange(N, device=embeddings.device).unsqueeze(1).expand(N, M).reshape(N * M)
            loss = F.cross_entropy(sim_flat, targets)
            
        elif self.loss_method == 'contrast':
            loss = 0.0
            for j in range(N):
                for i in range(M):
                    sim = sim_matrix[j, i]  # (N,)
                    pos_sim = sim[j]
                    neg_sims = torch.cat([sim[:j], sim[j+1:]])
                    neg_max, _ = torch.max(neg_sims, dim=0)
                    loss += 1 - torch.sigmoid(pos_sim) + torch.sigmoid(neg_max)
            loss = loss / (N * M)
        
        return loss
